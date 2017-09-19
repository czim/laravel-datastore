<?php
namespace Czim\DataStore\Stores;

use Czim\DataStore\Contracts\Resource\ResourceAdapterFactoryInterface;
use Czim\DataStore\Contracts\Resource\ResourceAdapterInterface;
use Czim\DataStore\Contracts\Stores\DataStoreFactoryInterface;
use Czim\DataStore\Contracts\Stores\DataStoreInterface;
use Czim\DataStore\Contracts\Stores\EloquentModelDataStoreInterface;
use Czim\DataStore\Contracts\Stores\EloquentRepositoryDataStoreInterface;
use Czim\DataStore\Contracts\Stores\Filtering\FilterHandlerInterface;
use Czim\DataStore\Contracts\Stores\Filtering\FilterStrategyFactoryInterface;
use Czim\DataStore\Contracts\Stores\Includes\IncludeDecoratorInterface;
use Czim\DataStore\Contracts\Stores\Includes\IncludeResolverInterface;
use Czim\DataStore\Contracts\Stores\Manipulation\DataManipulatorFactoryInterface;
use Czim\DataStore\Contracts\Stores\Manipulation\DataManipulatorInterface;
use Czim\Repository\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;
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
     * @return DataStoreInterface|EloquentModelDataStoreInterface
     */
    protected function makeForModel(Model $model)
    {
        /** @var DataStoreInterface|EloquentModelDataStoreInterface $store */
        $store = $this->getDataStoreInstance($model);

        if ( ! ($store instanceof EloquentModelDataStoreInterface)) {
            throw new RuntimeException(
                "Created data store instance of type '" . get_class($store) . "', expected EloquentModelDataStoreInterface"
            );
        }

        $this->provisionStoreInstance($store, $model);

        $store->setModel($model);

        $this->reset();

        return $store;
    }

    /**
     * Makes a data store for a repository instance.
     *
     * @param BaseRepositoryInterface $repository
     * @return DataStoreInterface|EloquentRepositoryDataStoreInterface
     */
    protected function makeForRepository(BaseRepositoryInterface $repository)
    {
        /** @var DataStoreInterface|EloquentRepositoryDataStoreInterface $store */
        $store = $this->getDataStoreInstance($repository);

        if ( ! ($store instanceof EloquentRepositoryDataStoreInterface)) {
            throw new RuntimeException(
                "Created data store instance of type '" . get_class($store) . "', expected EloquentRepositoryDataStoreInterface"
            );
        }

        $this->provisionStoreInstance($store, $repository);

        $store->setRepository($repository);

        $this->reset();

        return $store;
    }

    /**
     * @param DataStoreInterface $store
     * @param object             $object
     */
    protected function provisionStoreInstance(DataStoreInterface $store, $object)
    {
        $adapterFactory = $this->getResourceAdapterFactory();

        if ($object instanceof Model) {
            $adapter = $adapterFactory->makeForModel($object);
        } elseif ($object instanceof BaseRepositoryInterface) {
            $adapter = $adapterFactory->makeForRepository($object);
        } else {
            // @codeCoverageIgnoreStart
            throw new RuntimeException("Unknown object type, could not make resource adapter");
            // @codeCoverageIgnoreEnd
        }

        $driverString = $this->getDatabaseDriverString();

        $store
            ->setResourceAdapter($adapter)
            ->setStrategyDriver($driverString);

        if ($resolver = $this->getIncludeResolverForObject($object, $adapter)) {
            $resolver->setResourceAdapterFactory($adapterFactory);
            $store->setIncludeResolver($resolver);
        }

        if ($decorator = $this->getIncludeDecoratorForObject($object)) {
            $store->setIncludeDecorator($decorator);
        }

        if ($filter = $this->getFilterHandlerForObject($object)) {
            $filter
                ->setResourceAdapter($adapter)
                ->setStrategyFactory(
                    app(FilterStrategyFactoryInterface::class)->driver($driverString)
                );
            $store->setFilterHandler($filter);
        }

        if ($manipulator = $this->getDataManipulator($object)) {
            $store->setManipulator($manipulator);
        }

        if ($pageSize = array_get($this->config, 'pagination.size')) {
            $store->setDefaultPageSize($pageSize);
        }
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
     * @param object $object
     * @return DataStoreInterface
     */
    protected function getDataStoreInstance($object)
    {
        $class = $this->determineDataStoreClassForObject($object);

        return app($class);
    }

    /**
     * @param object $object
     * @return string
     */
    protected function determineDataStoreClassForObject($object)
    {
        $class = config("datastore.store-mapping.drivers.{$this->getDriverString()}." . get_class($object))
              ?: config('datastore.store-mapping.default.' . get_class($object));

        if ($class) {
            return $class;
        }

        if ($object instanceof BaseRepositoryInterface) {
            return EloquentRepositoryDataStore::class;
        }

        return EloquentDataStore::class;
    }

    /**
     * Returns the data manipulator for a given object.
     *
     * @param object $object
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
     * @param object                        $object
     * @param ResourceAdapterInterface|null $adapter
     * @return IncludeResolverInterface
     */
    protected function getIncludeResolverForObject($object, ResourceAdapterInterface $adapter = null)
    {
        /** @var IncludeResolverInterface $resolver */
        $resolver = app(IncludeResolverInterface::class);

        $model = $this->resolveObjectToModelInstance($object);

        $resolver->setModel($model, $adapter);

        return $resolver;
    }

    /**
     * @param object $object
     * @return IncludeDecoratorInterface
     */
    protected function getIncludeDecoratorForObject($object)
    {
        $model = $this->resolveObjectToModelInstance($object);

        $decoratorClass = config(
            'datastore.include.decorator.model-map.' . get_class($model),
            config('datastore.include.decorator.default')
        );

        if ( ! $decoratorClass) {
            return null;
        }

        $decorator = app($decoratorClass);

        $decorator->setModel($model);

        return $decorator;
    }

    /**
     * @param object $object
     * @return FilterHandlerInterface|null
     */
    protected function getFilterHandlerForObject($object)
    {
        $model = $this->resolveObjectToModelInstance($object);

        $filterClass = config(
            'datastore.filter.handler.model-map.' . get_class($model),
            config('datastore.filter.handler.default')
        );

        // @codeCoverageIgnoreStart
        if ( ! $filterClass) {
            return null;
        }
        // @codeCoverageIgnoreEnd

        $filter = app($filterClass);

        $filter->setModel($model);

        return $filter;
    }

    /**
     * @param object $object
     * @return Model
     */
    protected function resolveObjectToModelInstance($object)
    {
        if ($object instanceof BaseRepositoryInterface) {
            $object = $object->makeModel(false);

            // @codeCoverageIgnoreStart
            if ($object instanceof Builder) {
                $object = $object->getModel();
            }
            // @codeCoverageIgnoreEnd
        }

        // @codeCoverageIgnoreStart
        if ( ! ($object instanceof Model)) {
            throw new RuntimeException('Unsupported object for building dependency');
        }
        // @codeCoverageIgnoreEnd

        return $object;
    }

    /**
     * @return string
     */
    protected function getDriverString()
    {
        return $this->driver ?: config('datastore.drivers.datastore.default', 'model');
    }

}
