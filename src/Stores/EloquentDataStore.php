<?php
namespace Czim\DataStore\Stores;

use Czim\DataStore\Contracts\Resource\ResourceAdapterInterface;
use Czim\DataStore\Contracts\Stores\EloquentModelDataStoreInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class EloquentDataStore extends AbstractEloquentDataStore implements EloquentModelDataStoreInterface
{

    /**
     * @var Model
     */
    protected $model;

    /**
     * @var ResourceAdapterInterface
     */
    protected $resourceAdapter;

    /**
     * Strategies to use for JSON-API sort names.
     *
     * @var string[]
     */
    protected $sortStrategies = [];


    /**
     * Sets the model to use for accessing data.
     *
     * @param Model $model
     * @return $this
     */
    public function setModel(Model $model)
    {
        $this->model = $model;

        return $this;
    }

    /**
     * Returns a model instance.
     *
     * @return Model
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Returns a fresh query builder for the model.
     *
     * @return \Illuminate\Database\Query\Builder|Builder
     */
    protected function retrieveQuery()
    {
        $includes = $this->resolveIncludesForEagerLoading($this->includes);

        return $this->model->query()
            ->with($includes);
    }

    /**
     * Returns model by ID.
     *
     * @param mixed $id
     * @return mixed
     */
    protected function retrieveById($id)
    {
        return $this->retrieveQuery()
            ->where($this->model->getQualifiedKeyName(), $id)
            ->first();
    }

    /**
     * Returns many models by an array of IDs.
     *
     * @param array $ids
     * @return mixed
     */
    protected function retrieveManyById(array $ids)
    {
        return $this->retrieveQuery()
            ->whereIn($this->model->getQualifiedKeyName(), $ids)
            ->get();
    }

}
