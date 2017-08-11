<?php
namespace Czim\DataStore\Stores;

use Czim\DataStore\Contracts\Resource\ResourceAdapterFactoryInterface;
use Czim\DataStore\Contracts\Stores\DataStoreFactoryInterface;
use Czim\DataStore\Contracts\Stores\DataStoreInterface;
use Czim\Repository\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use UnexpectedValueException;

class DataStoreFactory implements DataStoreFactoryInterface
{

    /**
     * @var string|null
     */
    protected $adapterDriver;


    /**
     * Sets the adapter for the next make call.
     *
     * @param string|null $driver
     * @return $this
     */
    public function adapter($driver)
    {
        if (empty($driver)) {
            $this->adapterDriver = null;
        } else {
            $this->adapterDriver = $driver;
        }

        return $this;
    }

    /**
     * Makes a data store for a given object (type).
     *
     * @param object $object
     * @return DataStoreInterface
     */
    public function makeForObject($object)
    {
        if ($object instanceof Model) {
            return $this->makeForModel($object);
        }


        $adapterFactory = $this->getResourceAdapterFactory();

        if ($object instanceof BaseRepositoryInterface) {
            $resourceAdapter = $adapterFactory->makeForRepository($object);
            $store = new EloquentRepositoryDataStore($resourceAdapter);
            $store->setRepository($object);

        } else {
            throw new UnexpectedValueException("Could not create data store for class '" . get_class($object) . "'");
        }

        $this->resetAdapter();

        return $store;
    }

    /**
     * Makes a data store for an Eloquent model instance.
     *
     * @param Model $model
     * @return DataStoreInterface
     */
    public function makeForModel(Model $model)
    {
        $adapterFactory  = $this->getResourceAdapterFactory();
        $resourceAdapter = $adapterFactory->makeForModel($model);

        // todo
        $store = null;

        $this->resetAdapter();

        return $store;
    }

    /**
     * Resets the adapter to prepare for the next (fluent) call.
     */
    protected function resetAdapter()
    {
        $this->adapterDriver = null;
    }

    /**
     * @return ResourceAdapterFactoryInterface
     */
    protected function getResourceAdapterFactory()
    {
        return app($this->getResourceAdapterFactoryClass());
    }

    /**
     * @return string
     */
    protected function getResourceAdapterFactoryClass()
    {
        return config('datastore.drivers.adapter.drivers.' . $this->getAdapterDriverString() . '.factory');
    }

    /**
     * @return string
     */
    protected function getAdapterDriverString()
    {
        return $this->adapterDriver ?: config('datastore.drivers.adapter.default', 'default');
    }

}
