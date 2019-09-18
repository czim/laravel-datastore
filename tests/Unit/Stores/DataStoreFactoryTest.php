<?php
namespace Czim\DataStore\Test\Unit\Stores;

use Czim\DataStore\Contracts\Resource\ResourceAdapterInterface;
use Czim\DataStore\Contracts\Stores\DataStoreInterface;
use Czim\DataStore\Contracts\Stores\Manipulation\DataManipulatorFactoryInterface;
use Czim\DataStore\Contracts\Stores\Manipulation\DataManipulatorInterface;
use Czim\DataStore\Resource\JsonApi\JsonApiResourceAdapterFactory;
use Czim\DataStore\Stores\DataStoreFactory;
use Czim\DataStore\Stores\EloquentDataStore;
use Czim\DataStore\Stores\EloquentRepositoryDataStore;
use Czim\DataStore\Test\Helpers\Stores\Includes\TestIncludeDecorator;
use Czim\DataStore\Test\Helpers\Stores\TestModelStore;
use Czim\DataStore\Test\Helpers\Stores\TestRepositoryStore;
use Czim\DataStore\Test\ProvisionedTestCase;
use Czim\Repository\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Mockery;

class DataStoreFactoryTest extends ProvisionedTestCase
{

    // ------------------------------------------------------------------------------
    //      Model
    // ------------------------------------------------------------------------------

    /**
     * @test
     */
    function it_makes_a_datastore_for_a_model_using_the_default_driver()
    {
        $factory = new DataStoreFactory;

        $model = $this->getMockModel();

        /** @var Mockery\Mock|JsonApiResourceAdapterFactory $adapterFactory */
        $adapterFactory = Mockery::mock(JsonApiResourceAdapterFactory::class);
        /** @var Mockery\Mock|ResourceAdapterInterface $adapter */
        $adapter = Mockery::mock(ResourceAdapterInterface::class);

        $adapterFactory->shouldReceive('makeForModel')->once()->with($model)->andReturn($adapter);

        $this->app->instance(JsonApiResourceAdapterFactory::class, $adapterFactory);

        $store = $factory->driver(null)->makeForObject($model);

        static::assertInstanceOf(DataStoreInterface::class, $store);
    }

    /**
     * @test
     */
    function it_makes_a_datastore_for_a_model_using_the_default_driver_with_custom_config()
    {
        $factory = new DataStoreFactory;

        $model = $this->getMockModel();

        /** @var Mockery\Mock|JsonApiResourceAdapterFactory $adapterFactory */
        $adapterFactory = Mockery::mock(JsonApiResourceAdapterFactory::class);
        /** @var Mockery\Mock|ResourceAdapterInterface $adapter */
        $adapter = Mockery::mock(ResourceAdapterInterface::class);

        $adapterFactory->shouldReceive('makeForModel')->once()->with($model)->andReturn($adapter);

        $this->app->instance(JsonApiResourceAdapterFactory::class, $adapterFactory);

        $config = ['pagination' => ['size' => 13]];

        $store = $factory->config($config)->makeForObject($model);

        static::assertInstanceOf(DataStoreInterface::class, $store);
    }

    /**
     * @test
     */
    function it_makes_a_datastore_for_a_model_using_a_specified_driver()
    {
        $factory = new DataStoreFactory;

        $model = $this->getMockModel();

        $this->app['config']->set('datastore.drivers.datastore.drivers.testing.adapter', 'test-adapter');
        $this->app['config']->set('datastore.drivers.adapter.drivers.test-adapter.factory', 'testing-factory-binding');

        /** @var Mockery\Mock|JsonApiResourceAdapterFactory $adapterFactory */
        $adapterFactory = Mockery::mock(JsonApiResourceAdapterFactory::class);
        /** @var Mockery\Mock|ResourceAdapterInterface $adapter */
        $adapter = Mockery::mock(ResourceAdapterInterface::class);

        $adapterFactory->shouldReceive('makeForModel')->once()->with($model)->andReturn($adapter);

        $this->app->instance('testing-factory-binding', $adapterFactory);

        $store = $factory->driver('testing')->makeForObject($model);

        static::assertInstanceOf(DataStoreInterface::class, $store);
    }


    // ------------------------------------------------------------------------------
    //      Repository
    // ------------------------------------------------------------------------------

    /**
     * @test
     */
    function it_makes_a_datastore_for_a_repository_using_the_default_driver()
    {
        $factory = new DataStoreFactory;

        $model      = $this->getMockModel();
        $repository = $this->getMockRepository();

        $repository->shouldReceive('makeModel')->andReturn($model);

        /** @var Mockery\Mock|JsonApiResourceAdapterFactory $adapterFactory */
        $adapterFactory = Mockery::mock(JsonApiResourceAdapterFactory::class);
        /** @var Mockery\Mock|ResourceAdapterInterface $adapter */
        $adapter = Mockery::mock(ResourceAdapterInterface::class);

        $adapterFactory->shouldReceive('makeForRepository')->once()->with($repository)->andReturn($adapter);

        $this->app->instance(JsonApiResourceAdapterFactory::class, $adapterFactory);

        $store = $factory->makeForObject($repository);

        static::assertInstanceOf(DataStoreInterface::class, $store);
    }

    /**
     * @test
     */
    function it_makes_a_datastore_for_a_repository_using_the_default_driver_with_custom_config()
    {
        $factory = new DataStoreFactory;

        $model      = $this->getMockModel();
        $repository = $this->getMockRepository();

        $repository->shouldReceive('makeModel')->andReturn($model);

        /** @var Mockery\Mock|JsonApiResourceAdapterFactory $adapterFactory */
        $adapterFactory = Mockery::mock(JsonApiResourceAdapterFactory::class);
        /** @var Mockery\Mock|ResourceAdapterInterface $adapter */
        $adapter = Mockery::mock(ResourceAdapterInterface::class);

        $adapterFactory->shouldReceive('makeForRepository')->once()->with($repository)->andReturn($adapter);

        $this->app->instance(JsonApiResourceAdapterFactory::class, $adapterFactory);

        $config = ['pagination' => ['size' => 13]];

        $store = $factory->driver(null)->config($config)->makeForObject($repository);

        static::assertInstanceOf(DataStoreInterface::class, $store);
    }


    // ------------------------------------------------------------------------------
    //      Manipulator
    // ------------------------------------------------------------------------------

    /**
     * @test
     */
    function it_makes_a_datastore_with_configured_manipulator()
    {
        $factory = new DataStoreFactory;

        $model = $this->getMockModel();

        /** @var Mockery\Mock|JsonApiResourceAdapterFactory $adapterFactory */
        $adapterFactory = Mockery::mock(JsonApiResourceAdapterFactory::class);
        /** @var Mockery\Mock|ResourceAdapterInterface $adapter */
        $adapter = Mockery::mock(ResourceAdapterInterface::class);

        $adapterFactory->shouldReceive('makeForModel')->once()->with($model)->andReturn($adapter);

        $this->app->instance(JsonApiResourceAdapterFactory::class, $adapterFactory);

        // Manipulator mock/setup
        $this->app['config']->set('datastore.drivers.datastore.drivers.model.manipulator-factory', 'testing-manipulator-factory');

        /** @var Mockery\Mock|DataManipulatorInterface $manipulator */
        $manipulator = Mockery::mock(DataManipulatorInterface::class);

        /** @var Mockery\Mock|DataManipulatorFactoryInterface $manipulatorFactory */
        $manipulatorFactory = Mockery::mock(DataManipulatorFactoryInterface::class);
        $manipulatorFactory->shouldReceive('makeForObject')->with($model)->andReturn($manipulator);

        $this->app->instance('testing-manipulator-factory', $manipulatorFactory);

        $store = $factory->driver(null)->makeForObject($model);

        static::assertInstanceOf(DataStoreInterface::class, $store);
    }

    /**
     * @test
     */
    function it_makes_a_datastore_with_null_manipulator()
    {
        $factory = new DataStoreFactory;

        $model = $this->getMockModel();

        /** @var Mockery\Mock|JsonApiResourceAdapterFactory $adapterFactory */
        $adapterFactory = Mockery::mock(JsonApiResourceAdapterFactory::class);
        /** @var Mockery\Mock|ResourceAdapterInterface $adapter */
        $adapter = Mockery::mock(ResourceAdapterInterface::class);

        $adapterFactory->shouldReceive('makeForModel')->once()->with($model)->andReturn($adapter);

        $this->app->instance(JsonApiResourceAdapterFactory::class, $adapterFactory);

        // Manipulator mock/setup
        $this->app['config']->set('datastore.drivers.datastore.drivers.model.manipulator-factory', 'testing-manipulator-factory');
        /** @var Mockery\Mock|DataManipulatorFactoryInterface $manipulatorFactory */
        $manipulatorFactory = Mockery::mock(DataManipulatorFactoryInterface::class);
        $manipulatorFactory->shouldReceive('makeForObject')->with($model)->andReturn(null);

        $this->app->instance('testing-manipulator-factory', $manipulatorFactory);

        $store = $factory->driver(null)->makeForObject($model);

        static::assertInstanceOf(DataStoreInterface::class, $store);
    }


    // ------------------------------------------------------------------------------
    //      Mapping
    // ------------------------------------------------------------------------------

    /**
     * @test
     */
    function it_uses_a_configured_mapped_store_class_for_a_model()
    {
        $factory = new DataStoreFactory;

        $model = $this->getMockModel();

        $this->app['config']->set('datastore.store-mapping.drivers.model.' . get_class($model), TestModelStore::class);

        /** @var Mockery\Mock|JsonApiResourceAdapterFactory $adapterFactory */
        $adapterFactory = Mockery::mock(JsonApiResourceAdapterFactory::class);
        /** @var Mockery\Mock|ResourceAdapterInterface $adapter */
        $adapter = Mockery::mock(ResourceAdapterInterface::class);

        $adapterFactory->shouldReceive('makeForModel')->once()->with($model)->andReturn($adapter);

        $this->app->instance(JsonApiResourceAdapterFactory::class, $adapterFactory);

        $store = $factory->makeForObject($model);

        static::assertInstanceOf(TestModelStore::class, $store);
    }

    /**
     * @test
     */
    function it_uses_a_configured_mapped_store_class_for_a_model_with_default_fallback()
    {
        $factory = new DataStoreFactory;

        $model = $this->getMockModel();

        $this->app['config']->set('datastore.store-mapping.default.' . get_class($model), TestModelStore::class);

        /** @var Mockery\Mock|JsonApiResourceAdapterFactory $adapterFactory */
        $adapterFactory = Mockery::mock(JsonApiResourceAdapterFactory::class);
        /** @var Mockery\Mock|ResourceAdapterInterface $adapter */
        $adapter = Mockery::mock(ResourceAdapterInterface::class);

        $adapterFactory->shouldReceive('makeForModel')->once()->with($model)->andReturn($adapter);

        $this->app->instance(JsonApiResourceAdapterFactory::class, $adapterFactory);

        $store = $factory->makeForObject($model);

        static::assertInstanceOf(TestModelStore::class, $store);
    }

    /**
     * @test
     */
    function it_uses_a_configured_mapped_store_class_for_a_repository()
    {
        $factory = new DataStoreFactory;

        $model      = $this->getMockModel();
        $repository = $this->getMockRepository();

        $repository->shouldReceive('makeModel')->andReturn($model);

        $this->app['config']->set('datastore.store-mapping.drivers.model.' . get_class($repository), TestRepositoryStore::class);

        /** @var Mockery\Mock|JsonApiResourceAdapterFactory $adapterFactory */
        $adapterFactory = Mockery::mock(JsonApiResourceAdapterFactory::class);
        /** @var Mockery\Mock|ResourceAdapterInterface $adapter */
        $adapter = Mockery::mock(ResourceAdapterInterface::class);

        $adapterFactory->shouldReceive('makeForRepository')->once()->with($repository)->andReturn($adapter);

        $this->app->instance(JsonApiResourceAdapterFactory::class, $adapterFactory);

        $store = $factory->makeForObject($repository);

        static::assertInstanceOf(TestRepositoryStore::class, $store);
    }

    /**
     * @test
     */
    function it_throws_an_exception_if_a_mapped_model_store_does_not_implement_required_interface()
    {
        $this->expectException(\RuntimeException::class);

        $factory = new DataStoreFactory;

        $model = $this->getMockModel();

        $this->app['config']->set('datastore.store-mapping.drivers.model.' . get_class($model), static::class);

        /** @var Mockery\Mock|JsonApiResourceAdapterFactory $adapterFactory */
        $adapterFactory = Mockery::mock(JsonApiResourceAdapterFactory::class);

        $this->app->instance(JsonApiResourceAdapterFactory::class, $adapterFactory);

        $factory->makeForObject($model);
    }

    /**
     * @test
     */
    function it_throws_an_exception_if_a_mapped_repository_store_does_not_implement_required_interface()
    {
        $this->expectException(\RuntimeException::class);

        $factory = new DataStoreFactory;

        $repository = $this->getMockRepository();

        $this->app['config']->set('datastore.store-mapping.drivers.model.' . get_class($repository), static::class);

        /** @var Mockery\Mock|JsonApiResourceAdapterFactory $adapterFactory */
        $adapterFactory = Mockery::mock(JsonApiResourceAdapterFactory::class);

        $this->app->instance(JsonApiResourceAdapterFactory::class, $adapterFactory);

        $factory->makeForObject($repository);
    }


    // ------------------------------------------------------------------------------
    //      Includes
    // ------------------------------------------------------------------------------

    /**
     * @test
     */
    function it_uses_a_configured_default_include_decorator_class_for_a_model()
    {
        $isIncludeDecoratorInstantiated = false;

        $factory = new DataStoreFactory;

        $model = $this->getMockModel();

        $this->app['config']->set('datastore.include.decorator.default', TestIncludeDecorator::class);

        // Make a binding to spy on the instantiation of the decorator
        $this->app->bind(TestIncludeDecorator::class, function () use (&$isIncludeDecoratorInstantiated) {
            $isIncludeDecoratorInstantiated = true;
            return new TestIncludeDecorator;
        });

        /** @var Mockery\Mock|JsonApiResourceAdapterFactory $adapterFactory */
        $adapterFactory = Mockery::mock(JsonApiResourceAdapterFactory::class);
        /** @var Mockery\Mock|ResourceAdapterInterface $adapter */
        $adapter = Mockery::mock(ResourceAdapterInterface::class);

        $adapterFactory->shouldReceive('makeForModel')->with($model)->andReturn($adapter);

        $this->app->instance(JsonApiResourceAdapterFactory::class, $adapterFactory);

        $store = $factory->makeForObject($model);

        static::assertInstanceOf(EloquentDataStore::class, $store);
        static::assertTrue($isIncludeDecoratorInstantiated, 'Include decorator was not instantiated');
    }

    /**
     * @test
     */
    function it_uses_a_configured_mapped_include_decorator_class_for_a_model()
    {
        $isIncludeDecoratorInstantiated = false;

        $factory = new DataStoreFactory;

        $model = $this->getMockModel();

        $this->app['config']->set('datastore.include.decorator.model-map.' . get_class($model), TestIncludeDecorator::class);

        // Make a binding to spy on the instantiation of the decorator
        $this->app->bind(TestIncludeDecorator::class, function () use (&$isIncludeDecoratorInstantiated) {
            $isIncludeDecoratorInstantiated = true;
            return new TestIncludeDecorator;
        });

        /** @var Mockery\Mock|JsonApiResourceAdapterFactory $adapterFactory */
        $adapterFactory = Mockery::mock(JsonApiResourceAdapterFactory::class);
        /** @var Mockery\Mock|ResourceAdapterInterface $adapter */
        $adapter = Mockery::mock(ResourceAdapterInterface::class);

        $adapterFactory->shouldReceive('makeForModel')->with($model)->andReturn($adapter);

        $this->app->instance(JsonApiResourceAdapterFactory::class, $adapterFactory);

        $store = $factory->makeForObject($model);

        static::assertInstanceOf(EloquentDataStore::class, $store);
        static::assertTrue($isIncludeDecoratorInstantiated, 'Include decorator was not instantiated');
    }

    /**
     * @test
     */
    function it_uses_a_configured_mapped_include_decorator_class_for_a_model_repository()
    {
        $isIncludeDecoratorInstantiated = false;

        $factory = new DataStoreFactory;

        $model      = $this->getMockModel();
        $repository = $this->getMockRepository();

        $repository->shouldReceive('makeModel')->andReturn($model);

        $this->app['config']->set('datastore.include.decorator.model-map.' . get_class($model), TestIncludeDecorator::class);

        // Make a binding to spy on the instantiation of the decorator
        $this->app->bind(TestIncludeDecorator::class, function () use (&$isIncludeDecoratorInstantiated) {
            $isIncludeDecoratorInstantiated = true;
            return new TestIncludeDecorator;
        });

        /** @var Mockery\Mock|JsonApiResourceAdapterFactory $adapterFactory */
        $adapterFactory = Mockery::mock(JsonApiResourceAdapterFactory::class);
        /** @var Mockery\Mock|ResourceAdapterInterface $adapter */
        $adapter = Mockery::mock(ResourceAdapterInterface::class);

        $adapterFactory->shouldReceive('makeForRepository')->with($repository)->andReturn($adapter);

        $this->app->instance(JsonApiResourceAdapterFactory::class, $adapterFactory);

        $store = $factory->makeForObject($repository);

        static::assertInstanceOf(EloquentRepositoryDataStore::class, $store);
        static::assertTrue($isIncludeDecoratorInstantiated, 'Include decorator was not instantiated');
    }

    // ------------------------------------------------------------------------------
    //      Misc
    // ------------------------------------------------------------------------------

    /**
     * @test
     */
    function it_throws_an_exception_for_an_unsupported_object()
    {
        $this->expectException(\UnexpectedValueException::class);

        $factory = new DataStoreFactory;

        $factory->makeForObject($this);
    }


    /**
     * @return Mockery\MockInterface|Mockery\Mock|Model
     */
    protected function getMockModel()
    {
        return Mockery::mock(Model::class);
    }

    /**
     * @return Mockery\MockInterface|Mockery\Mock|BaseRepositoryInterface
     */
    protected function getMockRepository()
    {
        return Mockery::mock(BaseRepositoryInterface::class);
    }

}
