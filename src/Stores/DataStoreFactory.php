<?php
namespace Czim\DataStore\Stores;

use Czim\DataStore\Contracts\Resource\ResourceAdapterFactoryInterface;
use Czim\DataStore\Contracts\Stores\DataStoreFactoryInterface;
use Czim\DataStore\Contracts\Stores\DataStoreInterface;
use Czim\DataStore\Contracts\Stores\Manipulation\DataManipulatorFactoryInterface;
use Czim\DataStore\Contracts\Stores\Manipulation\DataManipulatorInterface;
use Czim\Repository\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use UnexpectedValueException;

class DataStoreFactory implements DataStoreFactoryInterface
{

    /**
     * Driver to use for fluent call.
     *
     * @var string|null
     */
    protected $driver;

    /**
     * Config to use for fluent call.
     *
     * @var array|null
     */
    protected $config;


    /**
     * Sets the driver for the next make call.
     *
     * @param string|null $driver
     * @return $this
     */
    public function driver($driver)
    {
        if (empty($driver)) {
            $this->driver = null;
        } else {
            $this->driver = $driver;
        }

        return $this;
    }

    /**
     * Sets the configuration for the next make call.
     *
     * @param array $config
     * @return $this
     */
    public function config(array $config = [])
    {
        $this->config = $config;

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

        if ($object instanceof BaseRepositoryInterface) {
            return $this->makeForRepository($object);
        }

        throw new UnexpectedValueException("Could not create data store for class '" . get_class($object) . "'");
    }

    /**
     * Makes a data store for an Eloquent model instance.
     *
     * @param Model $model
     * @return DataStoreInterface
     */
    protected function makeForModel(Model $model)
    {
        $adapterFactory = $this->getResourceAdapterFactory();

        $store = new EloquentDataStore;

        $store
            ->setResourceAdapter($adapterFactory->makeForModel($model))
            ->setStrategyDriver($this->getDatabaseDriverString())
            ->setModel($model);

        if ($manipulator = $this->getDataManipulator($model)) {
            $store->setManipulator($manipulator);
        }

        if ($pageSize = array_get($this->config, 'pagination.size')) {
            $store->setDefaultPageSize($pageSize);
        }

        $this->reset();

        return $store;
    }

    /**
     * Makes a data store for a repository instance.
     *
     * @param BaseRepositoryInterface $repository
     * @return EloquentRepositoryDataStore
     */
    protected function makeForRepository(BaseRepositoryInterface $repository)
    {
        $adapterFactory = $this->getResourceAdapterFactory();

        $store = new EloquentRepositoryDataStore;

        $store
            ->setResourceAdapter($adapterFactory->makeForRepository($repository))
            ->setStrategyDriver($this->getDatabaseDriverString())
            ->setRepository($repository);

        if ($manipulator = $this->getDataManipulator($repository)) {
            $store->setManipulator($manipulator);
        }

        if ($pageSize = array_get($this->config, 'pagination.size')) {
            $store->setDefaultPageSize($pageSize);
        }

        $this->reset();

        return $store;
    }

    /**
     * Resets the adapter to prepare for the next (fluent) call.
     */
    protected function reset()
    {
        $this->driver = null;
        $this->config = [];
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
        return config("datastore.drivers.adapter.drivers.{$this->getAdapterDriverString()}.factory");
    }

    /**
     * @return string
     */
    protected function getAdapterDriverString()
    {
        return config("datastore.drivers.datastore.drivers.{$this->getDriverString()}.adapter")
            ?: config('datastore.drivers.adapter.default', 'default');
    }

    /**
     * @return string
     */
    protected function getDatabaseDriverString()
    {
        return config("datastore.drivers.datastore.drivers.{$this->getDriverString()}.database")
            ?: config('datastore.drivers.database.default', 'mysql');
    }

    /**
     * Returns the data manipulator for a given object.
     *
     * @param $object
     * @return DataManipulatorInterface|null
     */
    protected function getDataManipulator($object)
    {
        $factory = $this->getDataManipulatorFactory();

        if ( ! $factory) {
            return null;
        }

        $manipulator = $factory->makeForObject($object);

        if ( ! $manipulator) {
            return null;
        }

        return $manipulator;
    }

    /**
     * @return DataManipulatorFactoryInterface
     */
    protected function getDataManipulatorFactory()
    {
        $class = $this->getDataManipulatorFactoryClass();

        if (empty($class)) {
            return null;
        }

        return app($class);
    }

    /**
     * @return string
     */
    protected function getDataManipulatorFactoryClass()
    {
        return config("datastore.drivers.datastore.drivers.{$this->getDriverString()}.manipulator-factory");
    }

    /**
     * @return string
     */
    protected function getDriverString()
    {
        return $this->driver ?: config('datastore.drivers.datastore.default', 'model');
    }

}
