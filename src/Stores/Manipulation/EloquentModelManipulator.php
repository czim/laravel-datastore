<?php
namespace Czim\DataStore\Stores\Manipulation;

use Czim\DataObject\Contracts\DataObjectInterface;
use Czim\DataStore\Contracts\Stores\Manipulation\DataManipulatorInterface;
use Czim\DataStore\Exceptions\RelationReplaceDisallowedException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use RuntimeException;

class EloquentModelManipulator implements DataManipulatorInterface
{

    /**
     * @var Model|null
     */
    protected $model;

    /**
     * The configuration for record manipulation.
     *
     * @var array
     */
    protected $config = [];


    /**
     * @param Model $model
     * @return $this
     */
    public function setModel(Model $model)
    {
        $this->model = $model;

        return $this;
    }

    /**
     * Returns the model set.
     *
     * @return Model|null
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Sets the configuration for record manipulation.
     *
     * @param array $config
     * @return $this
     */
    public function setConfig(array $config)
    {
        $this->config = $config;

        return $this;
    }

    /**
     * Returns the configuration.
     *
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }


    /**
     * Creates a new record with given JSON-API data.
     *
     * @param DataObjectInterface $data
     * @return mixed|false
     */
    public function create(DataObjectInterface $data)
    {
        $model = $this->model;

        return $model->create($data->toArray());
    }

    /**
     * Makes a record without persisting it.
     *
     * @param DataObjectInterface $data
     * @return false|mixed
     */
    public function make(DataObjectInterface $data)
    {
        $model = get_class($this->model);

        return new $model($data->toArray());
    }

    /**
     * Updates a record by ID with given JSON-API data.
     *
     * @param mixed               $id
     * @param DataObjectInterface $data
     * @return bool
     */
    public function updateById($id, DataObjectInterface $data)
    {
        $model = $this->model;

        /** @var Model $record */
        $record = $model->find($id);

        if ( ! $record) {
            throw new ModelNotFoundException();
        }

        return $record->update($data->toArray());
    }

    /**
     * Deletes a record by ID.
     *
     * @param mixed $id
     * @return bool
     */
    public function deleteById($id)
    {
        $model = $this->model;

        return (bool) $model::destroy($id);
    }

    /**
     * Attaches or replaces records for a relationship.
     *
     * @param mixed|Model              $parent
     * @param string                   $relation
     * @param mixed|Collection|Model[] $records
     * @param bool                     $detaching   if true, everything but the given records are detached
     * @return bool
     * @throws RelationReplaceDisallowedException
     */
    public function attachRelatedRecords($parent, $relation, $records, $detaching = false)
    {
        $this->verifyParentArgument($parent);
        $this->verifyArrayableArgument($records);

        $relationInstance = $this->getRelationForMethodName($relation, $parent);
        $singular         = $this->isRelationSingular($relationInstance);

        if ($singular) {
            // Normalized the single record to a single related record;
            $related = $this->getSingleRecordFromArrayableArgument($records);

            if ( ! ($related instanceof Model) && null !== $related) {
                throw new InvalidArgumentException("Singular relation record cannot be resolved to single model.");
            }

            return $this->performAttachRelatedForSingular($parent, $relation, $relationInstance, $related);
        }

        // Beyond this point, the relation is plural.

        // Plural relations that detach the old (replace everything) need to be allowed
        if ($detaching) {
            $this->throwExceptionIfDisallowedReplaceMany($relation);
        }

        if ( ! ($records instanceof Collection)) {
            $records = new Collection($records);
        }

        // Collect currently related class/key combinations if we need to delete them.
        // For BelongsToMany relations, process pivot data separately from the 'pivot' key
        // For HasMany & MorphMany detaching relations, nullify the foreign keys.
        return $this->performAttachRelatedForPlural($parent, $relation, $relationInstance, $records, $detaching);
    }

    /**
     * Performs the attaching of new related record for a singular relation.
     *
     * @param Model      $parent
     * @param string     $relation
     * @param Relation   $relationInstance
     * @param Model|null $related
     * @return bool
     */
    protected function performAttachRelatedForSingular(
        Model $parent,
        $relation,
        Relation $relationInstance,
        Model $related = null
    ) {
        // If the relationship is a has-one, any previously attached model
        // will be replaced by a new one, or disconnected if nullified.
        /** @var Model|null $previousRelated */
        $previousRelated = $parent->exists ? $parent->{$relation}()->first() : null;

        $different = (  $previousRelated && null === $related
            ||  $related && null === $previousRelated
            ||  get_class($previousRelated) !== get_class($related)
            ||  $previousRelated->getKey() !== $related->getKey()
        );

        if ( ! $different) {
            return true;
        }

        $deleting = $this->shouldDeleteOnDetach($relation);

        // If the relationship is a belongs-to, the related model must be persisted
        // and the parent model must be updated aswell.
        if ($relationInstance instanceof BelongsTo || $relationInstance instanceof MorphTo) {

            if ($related && ! $related->exists && ! $related->save()) {
                // @codeCoverageIgnoreStart
                return false;
                // @codeCoverageIgnoreEnd
            }

            if (null === $related) {
                $parent->{$relation}()->dissociate($related);
            } else {
                $parent->{$relation}()->associate($related);
            }

            if ( ! $parent->save()) {
                // @codeCoverageIgnoreStart
                return false;
                // @codeCoverageIgnoreEnd
            }

        } else {

            if (null !== $related && ! $parent->{$relation}()->save($related)) {
                // @codeCoverageIgnoreStart
                return false;
                // @codeCoverageIgnoreEnd
            }
        }

        // Handle the detached record, which may either be deleted,
        // or have its foreign keys nullified.
        if (null !== $previousRelated) {

            if ($deleting) {
                if ( ! $previousRelated->delete()) {
                    // @codeCoverageIgnoreStart
                    return false;
                    // @codeCoverageIgnoreEnd
                }

            } else {

                if ($relationInstance instanceof HasOne) {
                    $previousRelated->{$relationInstance->getForeignKeyName()} = null;
                    if ( ! $previousRelated->save()) {
                        // @codeCoverageIgnoreStart
                        return false;
                        // @codeCoverageIgnoreEnd
                    };
                }

                if ($relationInstance instanceof MorphOne) {
                    $previousRelated->{$relationInstance->getMorphType()} = null;
                    $previousRelated->{$relationInstance->getForeignKeyName()} = null;
                    if ( ! $previousRelated->save()) {
                        // @codeCoverageIgnoreStart
                        return false;
                        // @codeCoverageIgnoreEnd
                    }
                }
            }
        }

        return true;
    }

    /**
     * Performs the attaching of new related record for a plural relation.
     *
     * @param Model              $parent
     * @param string             $relation
     * @param Relation           $relationInstance
     * @param Collection|Model[] $records
     * @param bool               $detaching
     * @return bool
     */
    protected function performAttachRelatedForPlural(
        Model $parent,
        $relation,
        Relation $relationInstance,
        Collection $records,
        $detaching = false
    ) {
        // Any records not yet persisted, should be persisted before they
        // are saved for relations that depend on foreign keys.
        if ($relationInstance instanceof BelongsToMany || $relationInstance instanceof MorphToMany) {

            foreach ($records as $record) {
                if ( ! $record->exists && ! $record->save()) {
                    // @codeCoverageIgnoreStart
                    return false;
                    // @codeCoverageIgnoreEnd
                }
            }
        }

        if ($relationInstance instanceof BelongsToMany || $relationInstance instanceof MorphToMany) {
            return $this->performAttachRelatedForBelongsToMany(
                $parent, $relation, $relationInstance, $records, $detaching
            );
        }

        if ($relationInstance instanceof HasMany || $relationInstance instanceof MorphMany) {
            return $this->performAttachRelatedForHasMany(
                $parent, $relation, $relationInstance, $records, $detaching
            );
        }

        throw new RuntimeException("Unsupported relation instance type '" . get_class($relationInstance) . "'");
    }

    /**
     * @param Model              $parent
     * @param string             $relation
     * @param BelongsToMany      $relationInstance
     * @param Collection|Model[] $records
     * @param bool               $detaching
     * @return bool
     */
    protected function performAttachRelatedForBelongsToMany(
        Model $parent,
        $relation,
        BelongsToMany $relationInstance,
        Collection $records,
        $detaching = false
    ) {
        $deleting = $this->shouldDeleteOnDetach($relation);

        $relatedModel = $relationInstance->getRelated();

        // todo take pivot data into consideration
        // also for comparing previous/current state

        $newIds = $records->pluck($relatedModel->getKeyName());

        // Only when deleting detached, the difference must be analyzed
        // to find records that must be deleted.
        $detachIds = [];

        if ($deleting) {
            $previousIds = $relationInstance->pluck($relatedModel->getQualifiedKeyName());
            $detachIds   = $previousIds->diff($newIds)->toArray();
        }

        $relationInstance->sync($newIds, $detaching);

        if ($deleting) {
            foreach ($detachIds as $deleteId) {
                if ( ! $this->deletePreviouslyRelatedRecord($relation, $relatedModel->find($deleteId))) {
                    // @codeCoverageIgnoreStart
                    return false;
                    // @codeCoverageIgnoreEnd
                }
            }
        }

        return true;
    }

    /**
     * @param Model              $parent
     * @param string             $relation
     * @param HasOneOrMany       $relationInstance
     * @param Collection|Model[] $records
     * @param bool               $detaching
     * @return bool
     */
    protected function performAttachRelatedForHasMany(
        Model $parent,
        $relation,
        HasOneOrMany $relationInstance,
        Collection $records,
        $detaching = false
    ) {
        $deleting = $this->shouldDeleteOnDetach($relation);

        $relatedModel = $relationInstance->getRelated();

        $detachIds = [];

        if ($detaching) {
            $newIds      = $records->pluck($relatedModel->getKeyName());
            $previousIds = $relationInstance->pluck($relatedModel->getKeyName());
            $detachIds   = $previousIds->diff($newIds)->toArray();
        }

        $relationInstance->saveMany($records);

        if ( ! $detaching) {
            return true;
        }

        if ($deleting) {
            foreach ($detachIds as $deleteId) {
                if ( ! $this->deletePreviouslyRelatedRecord($relation, $relatedModel->find($deleteId))) {
                    // @codeCoverageIgnoreStart
                    return false;
                    // @codeCoverageIgnoreEnd
                }
            }

            return true;
        }

        // Nullify the foreign key on the detached models
        foreach ($detachIds as $detachId) {

            $detachRecord = $relatedModel->find($detachId);

            // @codeCoverageIgnoreStart
            if ( ! $detachRecord) {
                continue;
            }
            // @codeCoverageIgnoreEnd

            $detachRecord->forceFill([$relationInstance->getForeignKeyName() => null]);

            if ( ! $detachRecord->save()) {
                // @codeCoverageIgnoreStart
                return false;
                // @codeCoverageIgnoreEnd
            }
        }

        return true;
    }


    /**
     * Detaches records for a relationship.
     *
     * @param mixed|Model        $parent
     * @param string             $relation
     * @param Collection|Model[] $records
     * @return bool
     */
    public function detachRelatedRecords($parent, $relation, $records)
    {
        $this->verifyParentArgument($parent);
        $this->verifyArrayableArgument($records);

        $relationInstance = $this->getRelationForMethodName($relation);
        $singular         = $this->isRelationSingular($relationInstance);

        // Singular relationships are detachable by attaching null
        if ($singular) {

            // If the given record does not match for detachment
            $related  = $this->getSingleRecordFromArrayableArgument($records);
            $previous = $relationInstance->first();

            if (    ! $related || ! $previous
                ||  get_class($previous) !== get_class($related) ||  $previous->getKey() !== $related->getKey()
            ) {
                return false;
            }

            return $this->attachRelatedRecords($parent, $relation, [ null ], true);
        }

        // Plural relations
        if ( ! ($records instanceof Collection)) {
            $records = new Collection($records);
        }

        if ($relationInstance instanceof BelongsToMany || $relationInstance instanceof MorphToMany) {
            return $this->performDetachRelatedForBelongsToMany($parent, $relation, $relationInstance, $records);
        }

        if ($relationInstance instanceof HasMany || $relationInstance instanceof MorphMany) {
            return $this->performDetachRelatedForHasMany($parent, $relation, $relationInstance, $records);
        }

        throw new RuntimeException("Unsupported relation instance type '" . get_class($relationInstance) . "'");
    }

    /**
     * Detaches records by ID for a relationship.
     *
     * @param mixed|Model        $parent
     * @param string             $relation
     * @param Collection|mixed[] $ids
     * @return bool
     */
    public function detachRelatedRecordsById($parent, $relation, $ids)
    {
        $this->verifyParentArgument($parent);
        $this->verifyArrayableArgument($ids);

        $relationInstance = $this->getRelationForMethodName($relation);

        // todo

        return true;
    }

    /**
     * @param Model              $parent
     * @param string             $relation
     * @param BelongsToMany      $relationInstance
     * @param Collection|Model[] $records
     * @return bool
     */
    protected function performDetachRelatedForBelongsToMany(
        Model $parent,
        $relation,
        BelongsToMany $relationInstance,
        Collection $records
    ) {
        $deleting = $this->shouldDeleteOnDetach($relation);

        $relatedModel = $relationInstance->getRelated();

        $detachIds = $records->pluck($relatedModel->getKeyName());

        $matchedIds = $relationInstance
            ->whereIn($relatedModel->getQualifiedKeyName(), $detachIds->toArray())
            ->pluck($relatedModel->getQualifiedKeyName());

        if ( ! $matchedIds->count()) {
            return true;
        }

        $relationInstance->detach($matchedIds->toArray());

        if ($deleting) {
            foreach ($matchedIds as $deleteId) {
                if ( ! $this->deletePreviouslyRelatedRecord($relation, $relatedModel->find($deleteId))) {
                    // @codeCoverageIgnoreStart
                    return false;
                    // @codeCoverageIgnoreEnd
                }
            }
        }

        return true;
    }

    /**
     * @param Model              $parent
     * @param string             $relation
     * @param HasOneOrMany       $relationInstance
     * @param Collection|Model[] $records
     * @return bool
     */
    protected function performDetachRelatedForHasMany(
        Model $parent,
        $relation,
        HasOneOrMany $relationInstance,
        Collection $records
    ) {
        $deleting = $this->shouldDeleteOnDetach($relation);

        $relatedModel = $relationInstance->getRelated();

        $detachIds = $records->pluck($relatedModel->getKeyName());

        $matchedIds = $relationInstance
            ->whereIn($relatedModel->getQualifiedKeyName(), $detachIds->toArray())
            ->pluck($relatedModel->getQualifiedKeyName());

        if ( ! $matchedIds->count()) {
            return true;
        }

        if ($deleting) {
            foreach ($matchedIds as $deleteId) {
                if ( ! $this->deletePreviouslyRelatedRecord($relation, $relatedModel->find($deleteId))) {
                    // @codeCoverageIgnoreStart
                    return false;
                    // @codeCoverageIgnoreEnd
                }
            }

            return true;
        }

        // Nullify the foreign key on the detached models
        /** @var Collection|Model[] $matchedRecords */
        $matchedRecords = $records->whereIn($relatedModel->getKeyName(), $matchedIds->toArray());

        foreach ($matchedRecords as $detachRecord) {

            $detachRecord->forceFill([$relationInstance->getForeignKeyName() => null]);

            if ( ! $detachRecord->save()) {
                // @codeCoverageIgnoreStart
                return false;
                // @codeCoverageIgnoreEnd
            }
        }

        return true;
    }


    /**
     * Deletes a previously related record.
     *
     * This should only be called if the relation is configured to
     * delete on detaching.
     *
     * @param string $relation
     * @param Model  $record
     * @return bool|null
     */
    protected function deletePreviouslyRelatedRecord($relation, Model $record)
    {
        return $record->delete();
    }

    /**
     * Verifies that parent argument is valid.
     *
     * @param mixed $parent
     */
    protected function verifyParentArgument($parent)
    {
        if (null === $this->getModel()) {
            throw new InvalidArgumentException('No model set');
        }

        if ( ! is_a($parent, get_class($this->getModel()))) {
            throw new InvalidArgumentException('Parent object is of unexpected model');
        }
    }

    /**
     * Verifies that an argument is an array or arrayable.
     *
     * @param mixed  $parameter
     * @param string $argumentName
     */
    protected function verifyArrayableArgument($parameter, $argumentName = 'records')
    {
        if ( ! is_array($parameter) && ! ($parameter instanceof Collection)) {
            throw new InvalidArgumentException("{$argumentName} must be given as array or collection instance");
        }
    }

    /**
     * Normalizes a single record from a verified records list argument.
     *
     * @param array|Collection|mixed $records
     * @return Model
     */
    protected function getSingleRecordFromArrayableArgument($records)
    {
        if (is_array($records)) {
            return Arr::first($records);
        }

        if ($records instanceof Collection) {
            return $records->first();
        }

        // @codeCoverageIgnoreStart
        return $records;
        // @codeCoverageIgnoreEnd
    }

    /**
     * Returns the relation instance for a method name.
     *
     * @param string     $relationMethod
     * @param Model|null $parent
     * @return Relation
     */
    protected function getRelationForMethodName($relationMethod, Model $parent = null)
    {
        $parent = $parent ?: $this->getModel();

        $relationInstance = $parent->{$relationMethod}();

        if ( ! $relationInstance || ! ($relationInstance instanceof Relation)) {
            throw new InvalidArgumentException(
                "'{$relationMethod}' is not a usable relation method of " . get_class($this->getModel())
            );
        }

        return $relationInstance;
    }

    /**
     * Returns whether a given relation instance is singular.
     *
     * @param Relation $relation
     * @return bool
     */
    protected function isRelationSingular($relation)
    {
        return  $relation instanceof BelongsTo
            ||  $relation instanceof HasOne
            ||  $relation instanceof MorphOne
            ||  $relation instanceof MorphTo;
    }

    /**
     * Throws an exception if it is disallowed to replace many for a relation update.
     *
     * @param string $relation
     * @throws RelationReplaceDisallowedException
     */
    protected function throwExceptionIfDisallowedReplaceMany($relation)
    {
        if ( ! $this->isAllowedToReplaceManyForRelation($relation)) {
            throw new RelationReplaceDisallowedException;
        }
    }

    /**
     * Returns whether it is allowed to replace many for a relation update.
     *
     * @param string $relation
     * @return bool
     */
    protected function isAllowedToReplaceManyForRelation($relation)
    {
        if (null !== ($allowed = Arr::get($this->config, "allow-relationship-replace.{$relation}"))) {
            return (bool) $allowed;
        }

        return (bool) config('datastore.manipulation.allow-relationship-replace');
    }

    /**
     * Returns whether models detached for a given relation should be deleted.
     *
     * @param string $relation
     * @return bool
     */
    protected function shouldDeleteOnDetach($relation)
    {
        return (bool) Arr::get($this->config, "delete-detached.{$relation}");
    }

}
