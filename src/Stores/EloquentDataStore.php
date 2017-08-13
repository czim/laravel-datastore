<?php
namespace Czim\DataStore\Stores;

use Czim\DataStore\Contracts\Resource\ResourceAdapterInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class EloquentDataStore extends AbstractEloquentDataStore
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
     * Strategies to use for JSON-API sort names (without '-' prefix).
     *
     * @var string[]
     */
    protected $sortStrategies = [];


    /**
     * Sets the model to use for accessing data.
     *
     * @param Model $model
     */
    public function setModel(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Returns a model instance.
     *
     * @return Model
     */
    protected function getModel()
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
        return $this->model->query()
            ->with($this->includes);
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
            ->with($this->includes)
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
            ->with($this->includes)
            ->whereIn($this->model->getQualifiedKeyName(), $ids)
            ->get();
    }

}
