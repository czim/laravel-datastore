<?php
namespace Czim\DataStore\Test\Unit\Stores;

use Czim\DataStore\Context\RequestContext;
use Czim\DataStore\Context\SortKey;
use Czim\DataStore\Contracts\Resource\ResourceAdapterInterface;
use Czim\DataStore\Contracts\Stores\Filtering\FilterStrategyFactoryInterface;
use Czim\DataStore\Contracts\Stores\Filtering\FilterStrategyInterface;
use Czim\DataStore\Contracts\Stores\Manipulation\DataManipulatorInterface;
use Czim\DataStore\Contracts\Stores\Sorting\SortStrategyFactoryInterface;
use Czim\DataStore\Contracts\Stores\Sorting\SortStrategyInterface;
use Czim\DataStore\Stores\EloquentDataStore;
use Czim\DataStore\Test\Helpers\Data\TestData;
use Czim\DataStore\Test\Helpers\Models\TestModel;
use Czim\DataStore\Test\ProvisionedTestCase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Mockery;

class EloquentDataStoreTest extends ProvisionedTestCase
{

    /**
     * @test
     */
    function it_takes_a_resource_adapter()
    {
        $store = new EloquentDataStore;

        static::assertSame($store, $store->setResourceAdapter($this->getMockAdapter()));
    }

    /**
     * @test
     */
    function it_takes_a_strategy_driver_key()
    {
        $store = new EloquentDataStore;

        static::assertSame($store, $store->setStrategyDriver('test'));
    }

    /**
     * @test
     */
    function it_takes_a_manipulator_instance()
    {
        $store = new EloquentDataStore;

        static::assertSame($store, $store->setManipulator($this->getMockManipulator()));
    }

    /**
     * @test
     */
    function it_takes_default_page_size()
    {
        $store = new EloquentDataStore;

        static::assertSame($store, $store->setDefaultPageSize(12));
    }

    /**
     * @test
     */
    function it_takes_a_model()
    {
        $store = new EloquentDataStore;

        $model = new TestModel;

        static::assertSame($store, $store->setModel($model));

        static::assertSame($model, $store->getModel());
    }
    
    /**
     * @test
     */
    function it_returns_model_by_id()
    {
        $store = new EloquentDataStore;

        $store->setModel(new TestModel);

        $model = $this->createTestModel();

        /** @var TestModel $result */
        $result = $store->getById($model->getKey());

        static::assertInstanceOf(TestModel::class, $result);
        static::assertEquals($model->getKey(), $result->getKey());
    }

    /**
     * @test
     */
    function it_returns_many_models_by_id()
    {
        $store = new EloquentDataStore;

        $store->setModel(new TestModel);

        $model   = $this->createTestModel();
        $another = $this->createTestModel();

        /** @var TestModel[] $result */
        $result = $store->getManyById([ $model->getKey(), $another->getKey() ]);

        static::assertInstanceOf(Collection::class, $result);
        static::assertInstanceOf(TestModel::class, $result[0]);
        static::assertInstanceOf(TestModel::class, $result[1]);
    }

    /**
     * @test
     * @uses \Czim\DataStore\Context\RequestContext
     */
    function it_returns_models_by_empty_context()
    {
        $store = new EloquentDataStore;

        $adapter = $this->getMockAdapter();

        $store->setModel(new TestModel);
        $store->setResourceAdapter($adapter);

        $this->createTestModel();
        $this->createTestModel();
        $this->createTestModel();

        $context = new RequestContext;

        // Prepare contextual mocks
        $adapter->shouldReceive('availableFilterKeys')->once()->andReturn(['id']);
        $adapter->shouldReceive('availableSortKeys')->once()->andReturn(['id']);
        $adapter->shouldReceive('defaultFilters')->once()->andReturn([]);
        $adapter->shouldReceive('defaultSorting')->once()->andReturn([]);
        $adapter->shouldReceive('dataKeyForAttribute')->with('id')->andReturn('id');

        $result = $store->getByContext($context);

        static::assertInstanceOf(Collection::class, $result);
        static::assertCount(3, $result);
    }

    /**
     * @test
     * @uses \Czim\DataStore\Context\RequestContext
     */
    function it_returns_models_by_context_with_pagination_filters_and_sorting()
    {
        $store = new EloquentDataStore;

        $adapter = $this->getMockAdapter();

        $store->setModel(new TestModel);
        $store->setResourceAdapter($adapter);

        $model   = $this->createTestModel();
        $another = $this->createTestModel();
        $this->createTestModel();

        $context = new RequestContext;
        $context->filters = [
            'id' => [ $model->getKey(), $another->getKey() ],
        ];
        $context->sorting = [ new SortKey('id', true) ];
        $context->page_size = 1;
        $context->page_number = 2;

        // Prepare contextual mocks
        $adapter->shouldReceive('availableFilterKeys')->once()->andReturn(['id']);
        $adapter->shouldReceive('availableSortKeys')->once()->andReturn(['id']);
        $adapter->shouldReceive('dataKeyForAttribute')->with('id')->andReturn('id');

        $filterStrategy = $this->getMockFilterStrategy();
        $filterStrategy->shouldReceive('apply')->once()->andReturnUsing(function ($query, $column, $value) {
            /** @var Builder $query */
            return $query->whereIn($column, $value);
        });

        $strategyFactory = $this->getMockFilterStrategyFactory();
        $strategyFactory->shouldReceive('driver')->andReturnSelf();
        $strategyFactory->shouldReceive('make')->andReturn($filterStrategy);

        $sortStrategy = $this->getMockSortStrategy();
        $sortStrategy->shouldReceive('apply')->once()->andReturnUsing(function ($query, $column, $reverse) {
            /** @var Builder $query */
            return $query->orderBy($column, $reverse ? 'desc' : 'asc');
        });

        $sortFactory = $this->getMockSortStrategyFactory();
        $sortFactory->shouldReceive('driver')->andReturnSelf();
        $sortFactory->shouldReceive('make')->andReturn($sortStrategy);

        $this->app->instance(FilterStrategyFactoryInterface::class, $strategyFactory);
        $this->app->instance(SortStrategyFactoryInterface::class, $sortFactory);


        /** @var LengthAwarePaginator $result */
        $result = $store->getByContext($context);

        static::assertInstanceOf(LengthAwarePaginator::class, $result);
        static::assertCount(1, $result);
        static::assertEquals(2, $result->currentPage());
        static::assertEquals(2, $result->lastPage());
        static::assertEquals(2, $result->total());
        static::assertEquals(1, $result->perPage());

        static::assertEquals(1, $result->items()[0]->id);
    }

    /**
     * @test
     * @uses \Czim\DataStore\Context\RequestContext
     */
    function it_uses_default_sorting_and_filters_if_none_specified_in_context()
    {
        $store = new EloquentDataStore;

        $adapter = $this->getMockAdapter();

        $store->setModel(new TestModel);
        $store->setResourceAdapter($adapter);

        $model   = $this->createTestModel();
        $another = $this->createTestModel();
        $this->createTestModel();

        $context = new RequestContext;
        $context->page_size = 1;
        $context->page_number = 2;

        // Prepare contextual mocks
        $adapter->shouldReceive('availableFilterKeys')->andReturn(['id']);
        $adapter->shouldReceive('defaultFilters')->once()->andReturn(['id' => [ $model->getKey(), $another->getKey() ]]);
        $adapter->shouldReceive('availableSortKeys')->andReturn(['id']);
        $adapter->shouldReceive('defaultSorting')->once()->andReturn([ new SortKey('id', true) ]);
        $adapter->shouldReceive('dataKeyForAttribute')->with('id')->andReturn('id');

        $filterStrategy = $this->getMockFilterStrategy();
        $filterStrategy->shouldReceive('apply')->once()->andReturnUsing(function ($query, $column, $value) {
            /** @var Builder $query */
            return $query->whereIn($column, $value);
        });

        $strategyFactory = $this->getMockFilterStrategyFactory();
        $strategyFactory->shouldReceive('driver')->andReturnSelf();
        $strategyFactory->shouldReceive('make')->andReturn($filterStrategy);

        $sortStrategy = $this->getMockSortStrategy();
        $sortStrategy->shouldReceive('apply')->once()->andReturnUsing(function ($query, $column, $reverse) {
            /** @var Builder $query */
            return $query->orderBy($column, $reverse ? 'desc' : 'asc');
        });

        $sortFactory = $this->getMockSortStrategyFactory();
        $sortFactory->shouldReceive('driver')->andReturnSelf();
        $sortFactory->shouldReceive('make')->andReturn($sortStrategy);

        $this->app->instance(FilterStrategyFactoryInterface::class, $strategyFactory);
        $this->app->instance(SortStrategyFactoryInterface::class, $sortFactory);


        /** @var LengthAwarePaginator $result */
        $result = $store->getByContext($context);

        static::assertInstanceOf(LengthAwarePaginator::class, $result);
        static::assertCount(1, $result);
        static::assertEquals(2, $result->currentPage());
        static::assertEquals(2, $result->lastPage());
        static::assertEquals(2, $result->total());
        static::assertEquals(1, $result->perPage());

        static::assertEquals(1, $result->items()[0]->id);
    }


    // ------------------------------------------------------------------------------
    //      Manipulation (passthru)
    // ------------------------------------------------------------------------------

    /**
     * @test
     */
    function it_passes_create_through_to_manipulator()
    {
        $store = new EloquentDataStore;

        $model = $this->createTestModel();
        $data = new TestData(['test' => true]);

        $manipulator = $this->getMockManipulator();
        $manipulator->shouldReceive('create')->once()->with($data)->andReturn($model);

        $store->setManipulator($manipulator);

        static::assertSame($model, $store->create($data));
    }

    /**
     * @test
     * @expectedException \Czim\DataStore\Exceptions\FeatureNotSupportedException
     */
    function it_throws_an_exception_for_create_if_no_manipulator_is_set()
    {
        $store = new EloquentDataStore;

        $store->create(new TestData(['test' => true]));
    }

    /**
     * @test
     */
    function it_passes_update_through_to_manipulator()
    {
        $store = new EloquentDataStore;

        $data = new TestData(['test' => true]);

        $manipulator = $this->getMockManipulator();
        $manipulator->shouldReceive('updateById')->once()->with(1, $data)->andReturn(true);

        $store->setManipulator($manipulator);

        static::assertTrue($store->updateById(1, $data));
    }

    /**
     * @test
     * @expectedException \Czim\DataStore\Exceptions\FeatureNotSupportedException
     */
    function it_throws_an_exception_for_update_if_no_manipulator_is_set()
    {
        $store = new EloquentDataStore;

        $store->updateById(1, new TestData(['test' => true]));
    }

    /**
     * @test
     */
    function it_passes_delete_through_to_manipulator()
    {
        $store = new EloquentDataStore;

        $manipulator = $this->getMockManipulator();
        $manipulator->shouldReceive('deleteById')->once()->with(1)->andReturn(true);

        $store->setManipulator($manipulator);

        static::assertTrue($store->deleteById(1));
    }

    /**
     * @test
     * @expectedException \Czim\DataStore\Exceptions\FeatureNotSupportedException
     */
    function it_throws_an_exception_for_delete_if_no_manipulator_is_set()
    {
        $store = new EloquentDataStore;

        $store->deleteById(1);
    }


    /**
     * @return Mockery\MockInterface|Mockery\Mock|DataManipulatorInterface
     */
    protected function getMockManipulator()
    {
        return Mockery::mock(DataManipulatorInterface::class);
    }

    /**
     * @return Mockery\MockInterface|Mockery\Mock|ResourceAdapterInterface
     */
    protected function getMockAdapter()
    {
        return Mockery::mock(ResourceAdapterInterface::class);
    }

    /**
     * @return Mockery\MockInterface|Mockery\Mock|FilterStrategyFactoryInterface
     */
    protected function getMockFilterStrategyFactory()
    {
        return Mockery::mock(FilterStrategyFactoryInterface::class);
    }

    /**
     * @return Mockery\MockInterface|Mockery\Mock|SortStrategyFactoryInterface
     */
    protected function getMockSortStrategyFactory()
    {
        return Mockery::mock(SortStrategyFactoryInterface::class);
    }

    /**
     * @return Mockery\MockInterface|Mockery\Mock|FilterStrategyInterface
     */
    protected function getMockFilterStrategy()
    {
        return Mockery::mock(FilterStrategyInterface::class);
    }

    /**
     * @return Mockery\MockInterface|Mockery\Mock|SortStrategyInterface
     */
    protected function getMockSortStrategy()
    {
        return Mockery::mock(SortStrategyInterface::class);
    }

}
