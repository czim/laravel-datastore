<?php
namespace Czim\DataStore\Contracts\Stores;

use Illuminate\Database\Eloquent\Model;

interface DataStoreFactoryInterface
{

    /**
     * Sets the adapter for the next make call.
     *
     * @param string|null $driver
     * @return $this
     */
    public function adapter($driver);

    /**
     * Makes a data store for a given object (type).
     *
     * @param object $object
     * @return DataStoreInterface
     */
    public function makeForObject($object);

    /**
     * Makes a data store for an Eloquent model instance.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return DataStoreInterface
     */
    public function makeForModel(Model $model);

}
