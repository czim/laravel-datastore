<?php
namespace Czim\DataStore\Stores;

use Czim\DataObject\Contracts\DataObjectInterface;
use Czim\DataStore\Contracts\Resource\ResourceAdapterInterface;
use Czim\Repository\Contracts\BaseRepositoryInterface;
use Czim\Repository\Criteria\Common\WithRelations;
use Czim\Repository\Enums\CriteriaKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class EloquentRepositoryDataStore extends AbstractEloquentDataStore
{

    /**
     * @var BaseRepositoryInterface
     */
    protected $repository;

    /**
     * @var ResourceAdapterInterface
     */
    protected $resourceAdapter;

    /**
     * Strategies to use for JSON-API sort names (without '-' prefix).
     *
     * @var string[]
     */
    protected $sortStrategies = [];


    /**
     * Sets the repository to use for accessing data.
     *
     * @param BaseRepositoryInterface $repository
     */
    public function setRepository(BaseRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Returns a model instance.
     *
     * @return Model
     */
    protected function getModel()
    {
        $model = $this->repository->makeModel(false);

        if ($model instanceof Builder) {
            $model = $model->getModel();
        }

        /** @var Model $model */

        return $model;
    }

    /**
     * Returns a fresh query builder for the model.
     *
     * @return \Illuminate\Database\Query\Builder|Builder
     */
    protected function retrieveQuery()
    {
        $this->repository->pushCriteriaOnce(new WithRelations($this->includes), CriteriaKey::WITH);

        return $this->repository->query();
    }

    /**
     * Returns model by ID.
     *
     * @param mixed $id
     * @return mixed
     */
    protected function retrieveById($id)
    {
        $this->repository->pushCriteriaOnce(new WithRelations($this->includes), CriteriaKey::WITH);

        return $this->repository->find($id);
    }

    /**
     * Returns many models by an array of IDs.
     *
     * @param array $ids
     * @return mixed
     */
    protected function retrieveManyById(array $ids)
    {
        $this->repository->pushCriteriaOnce(new WithRelations($this->includes), CriteriaKey::WITH);

        $key = $this->getModel()->getQualifiedKeyName();

        return $this->repository->query()->whereIn($key, $ids)->get();
    }


    // ------------------------------------------------------------------------------
    //      Updating
    // ------------------------------------------------------------------------------

    /**
     * Creates a new record with given JSON-API data.
     *
     * @param DataObjectInterface $data
     * @return mixed|false
     */
    public function create(DataObjectInterface $data)
    {
        // TODO: Implement create() method.
    }

    /**
     * Updates a record by ID with given JSON-API data.
     *
     * @param string              $id
     * @param DataObjectInterface $data
     * @return bool
     */
    public function updatedById($id, DataObjectInterface $data)
    {
        // TODO: Implement updatedById() method.
    }

    /**
     * Deletes a record by ID.
     *
     * @param string $id
     * @return bool
     */
    public function deleteById($id)
    {
        // TODO: Implement deleteById() method.
    }

}
