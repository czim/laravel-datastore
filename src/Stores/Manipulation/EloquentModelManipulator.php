<?php
namespace Czim\DataStore\Stores\Manipulation;

use Czim\DataObject\Contracts\DataObjectInterface;
use Czim\DataStore\Contracts\Stores\Manipulation\DataManipulatorInterface;
use Czim\DataStore\Exceptions\RelationReplaceDisallowedException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use InvalidArgumentException;

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

        return $model::create($data->toArray());
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
        $record = $model::find($id);

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
        $deleting         = $this->shouldDeleteOnDetach($relation);

        if ($singular) {
            // Normalized the single record to a single related record;
            if (is_array($records)) {
                $related = array_first($records);
            } elseif ($records instanceof Collection) {
                $related = $records->first();
            } else {
                // @codeCoverageIgnoreStart
                $related = $records;
                // @codeCoverageIgnoreEnd
            }

            if ( ! ($related instanceof Model) && null !== $related) {
                throw new InvalidArgumentException("Singular relation record cannot be resolved to single model.");
            }

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

        // Beyond this point, the relation is plural.

        // Plural relations that detach the old (replace everything) need to be allowed
        if ($detaching) {
            $this->throwExceptionIfDisallowedReplaceMany($relation);
        }

        // todo
        // Collect currently related class/key combinations if we need to delete them.
        // For BelongsToMany relations, process pivot data separately from the 'pivot' key
        // For HasMany & MorphMany detaching relations, nullify the foreign keys.



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

        // todo

        return true;
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
        return false !== config('datastore.manipulation.allow-relationship-replace')
            && false !== array_get($this->config, "allow-relationship-replace.{$relation}");
    }

    /**
     * Returns whether models detached for a given relation should be deleted.
     *
     * @param string $relation
     * @return bool
     */
    protected function shouldDeleteOnDetach($relation)
    {
        return (bool) array_get($this->config, "delete-detached.{$relation}");
    }

}
