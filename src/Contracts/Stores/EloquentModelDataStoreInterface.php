<?php
namespace Czim\DataStore\Contracts\Stores;

use Illuminate\Database\Eloquent\Model;

interface EloquentModelDataStoreInterface
{

    /**
     * Sets the model to use for accessing data.
     *
     * @param Model $model
     * @return $this
     */
    public function setModel(Model $model);

    /**
     * Returns a model instance.
     *
     * @return Model
     */
    public function getModel();

}
