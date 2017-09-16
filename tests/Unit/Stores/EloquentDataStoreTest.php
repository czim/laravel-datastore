<?php
namespace Czim\DataStore\Test\Unit\Stores;

use Czim\DataObject\Contracts\DataObjectInterface;
use Czim\DataStore\Context\RequestContext;
use Czim\DataStore\Context\SortKey;
use Czim\DataStore\Contracts\Resource\ResourceAdapterInterface;
use Czim\DataStore\Contracts\Stores\Filtering\FilterStrategyFactoryInterface;
use Czim\DataStore\Contracts\Stores\Filtering\FilterStrategyInterface;
use Czim\DataStore\Contracts\Stores\Includes\IncludeDecoratorInterface;
use Czim\DataStore\Contracts\Stores\Includes\IncludeResolverInterface;
use Czim\DataStore\Contracts\Stores\Manipulation\DataManipulatorInterface;
use Czim\DataStore\Contracts\Stores\Sorting\SortStrategyFactoryInterface;
use Czim\DataStore\Contracts\Stores\Sorting\SortStrategyInterface;
use Czim\DataStore\Stores\EloquentDataStore;
use Czim\DataStore\Test\Helpers\Data\TestData;
use Czim\DataStore\Test\Helpers\Models\TestModel;
use Czim\DataStore\Test\Helpers\Models\TestPost;
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
    function it_takes_an_include_decorator()
    {
        $store = new EloquentDataStore;

        static::assertSame($store, $store->setIncludeDecorator($this->getMockIncludeDecorator()));
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
    //      Includes
    // ------------------------------------------------------------------------------

    /**
     * @test
     */
    function it_resolves_include_relations_for_find_by_id()
    {
        $this->setUpRelatedDatabase();

        $model = $this->createRelatedTestModel();

        $adapter  = $this->getMockAdapter();
        $resolver = $this->getMockIncludeResolver();

        $store = new EloquentDataStore;

        $store->setModel(new TestModel);
        $store->setResourceAdapter($adapter);
        $store->setIncludeResolver($resolver);

        $resolver->shouldReceive('resolve')->with(['relation', 'another'])->andReturn(['testRelatedModels', 'testMorphRelatedModels']);

        /** @var TestModel $result */
        $result = $store->getById($model->getKey(), ['relation', 'another']);

        static::assertInstanceOf(TestModel::class, $result);
        static::assertEquals($model->getKey(), $result->getKey());
        static::assertTrue($result->relationLoaded('testRelatedModels'), 'Eager loading not applied');
        static::assertTrue($result->relationLoaded('testMorphRelatedModels'), 'Eager loading not applied');
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    function it_throws_an_exception_for_detach_by_id_if_include_could_not_be_resolved()
    {
        $parent = new TestPost;

        $store = new EloquentDataStore;

        $manipulator = $this->getMockManipulator();
        $manipulator->shouldReceive('detachRelatedRecordsById')->never()->andReturn(true);

        $adapter = $this->getMockAdapter();
        $adapter->shouldReceive('dataKeyForInclude')->once()->with('include')->andReturn(false);

        $store->setManipulator($manipulator);
        $store->setResourceAdapter($adapter);

        $store->detachRelatedRecordsById($parent, 'include', [ 1 ]);
    }

    /**
     * @test
     */
    function it_decorates_includes_if_an_include_decorator_is_set()
    {
        $this->setUpRelatedDatabase();

        $model = $this->createRelatedTestModel();

        $adapter   = $this->getMockAdapter();
        $resolver  = $this->getMockIncludeResolver();
        $decorator = $this->getMockIncludeDecorator();

        $store = new EloquentDataStore;

        $store->setModel(new TestModel);
        $store->setResourceAdapter($adapter);
        $store->setIncludeResolver($resolver);
        $store->setIncludeDecorator($decorator);

        $resolver->shouldReceive('resolve')->with(['relation'])->andReturn(['testRelatedModels']);

        $decorator->shouldReceive('decorate')
            ->with(['testRelatedModels'], false)
            ->andReturn(['testRelatedModels', 'testMorphRelatedModels']);

        /** @var TestModel $result */
        $result = $store->getById($model->getKey(), ['relation']);

        static::assertInstanceOf(TestModel::class, $result);
        static::assertEquals($model->getKey(), $result->getKey());
        static::assertTrue($result->relationLoaded('testRelatedModels'), 'Eager loading not applied');
        static::assertTrue($result->relationLoaded('testMorphRelatedModels'), 'Eager loading not applied');
    }

    /**
     * @test
     */
    function it_decorates_includes_for_many_if_an_include_decorator_is_set()
    {
        $this->setUpRelatedDatabase();

        $model = $this->createRelatedTestModel();

        $adapter   = $this->getMockAdapter();
        $resolver  = $this->getMockIncludeResolver();
        $decorator = $this->getMockIncludeDecorator();

        $store = new EloquentDataStore;

        $store->setModel(new TestModel);
        $store->setResourceAdapter($adapter);
        $store->setIncludeResolver($resolver);
        $store->setIncludeDecorator($decorator);

        $resolver->shouldReceive('resolve')->with(['relation'])->andReturn(['testRelatedModels']);

        $decorator->shouldReceive('decorate')
            ->with(['testRelatedModels'], true)
            ->andReturn(['testRelatedModels', 'testMorphRelatedModels']);

        /** @var TestModel $result */
        $result = $store->getManyById([ $model->getKey() ], ['relation']);

        static::assertInstanceOf(Collection::class, $result);
        static::assertInstanceOf(TestModel::class, $result->first());
        static::assertEquals($model->getKey(), $result->first()->getKey());
        static::assertTrue($result->first()->relationLoaded('testRelatedModels'), 'Eager loading not applied');
        static::assertTrue($result->first()->relationLoaded('testMorphRelatedModels'), 'Eager loading not applied');
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

        $adapter = $this->getMockAdapter();
        $adapter->shouldReceive('dataKeyForAttribute')->once()->with('test')->andReturn('converted');

        $manipulator = $this->getMockManipulator();
        $manipulator->shouldReceive('create')->once()
            ->with(Mockery::type(DataObjectInterface::class))
            ->andReturn($model);

        $store->setResourceAdapter($adapter);
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
    function it_passes_make_through_to_manipulator()
    {
        $store = new EloquentDataStore;

        $model = $this->createTestModel();
        $data = new TestData(['test' => true]);

        $adapter = $this->getMockAdapter();
        $adapter->shouldReceive('dataKeyForAttribute')->once()->with('test')->andReturn('converted');

        $manipulator = $this->getMockManipulator();
        $manipulator->shouldReceive('make')->once()
            ->with(Mockery::type(DataObjectInterface::class))
            ->andReturn($model);

        $store->setResourceAdapter($adapter);
        $store->setManipulator($manipulator);

        static::assertSame($model, $store->make($data));
    }

    /**
     * @test
     * @expectedException \Czim\DataStore\Exceptions\FeatureNotSupportedException
     */
    function it_throws_an_exception_for_make_if_no_manipulator_is_set()
    {
        $store = new EloquentDataStore;

        $store->make(new TestData(['test' => true]));
    }

    /**
     * @test
     */
    function it_passes_update_through_to_manipulator()
    {
        $store = new EloquentDataStore;

        $data = new TestData(['test' => true]);

        $adapter = $this->getMockAdapter();
        $adapter->shouldReceive('dataKeyForAttribute')->once()->with('test')->andReturn('converted');

        $manipulator = $this->getMockManipulator();
        $manipulator->shouldReceive('updateById')->once()
            ->with(1, Mockery::type(DataObjectInterface::class))
            ->andReturn(true);

        $store->setResourceAdapter($adapter);
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
     * @test
     */
    function it_passes_attach_related_records_through_to_manipulator()
    {
        $parent = new TestPost;

        $store = new EloquentDataStore;

        $manipulator = $this->getMockManipulator();
        $manipulator->shouldReceive('attachRelatedRecords')->once()
            ->with($parent, 'included', [ $parent ], true)
            ->andReturn(true);

        $adapter = $this->getMockAdapter();
        $adapter->shouldReceive('dataKeyForInclude')->once()->with('include')->andReturn('included');

        $store->setManipulator($manipulator);
        $store->setResourceAdapter($adapter);

        static::assertTrue($store->attachRelatedRecords($parent, 'include', [ $parent ], true));
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    function it_throws_an_exception_for_attach_if_include_could_not_be_resolved()
    {
        $parent = new TestPost;

        $store = new EloquentDataStore;

        $manipulator = $this->getMockManipulator();
        $manipulator->shouldReceive('attachRelatedRecords')->never()->andReturn(true);

        $adapter = $this->getMockAdapter();
        $adapter->shouldReceive('dataKeyForInclude')->once()->with('include')->andReturn(false);

        $store->setManipulator($manipulator);
        $store->setResourceAdapter($adapter);

        $store->attachRelatedRecords($parent, 'include', [ $parent ], true);
    }

    /**
     * @test
     */
    function it_passes_detach_related_records_through_to_manipulator()
    {
        $parent = new TestPost;

        $store = new EloquentDataStore;

        $manipulator = $this->getMockManipulator();
        $manipulator->shouldReceive('detachRelatedRecords')->once()
            ->with($parent, 'included', [ $parent ])
            ->andReturn(true);

        $adapter = $this->getMockAdapter();
        $adapter->shouldReceive('dataKeyForInclude')->once()->with('include')->andReturn('included');

        $store->setManipulator($manipulator);
        $store->setResourceAdapter($adapter);

        static::assertTrue($store->detachRelatedRecords($parent, 'include', [ $parent ]));
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    function it_throws_an_exception_for_detach_if_include_could_not_be_resolved()
    {
        $parent = new TestPost;

        $store = new EloquentDataStore;

        $manipulator = $this->getMockManipulator();
        $manipulator->shouldReceive('detachRelatedRecords')->never()->andReturn(true);

        $adapter = $this->getMockAdapter();
        $adapter->shouldReceive('dataKeyForInclude')->once()->with('include')->andReturn(false);

        $store->setManipulator($manipulator);
        $store->setResourceAdapter($adapter);

        $store->detachRelatedRecords($parent, 'include', [ $parent ]);
    }

    /**
     * @test
     */
    function it_passes_detach_related_records_by_id_through_to_manipulator()
    {
        $parent = new TestPost;

        $store = new EloquentDataStore;

        $manipulator = $this->getMockManipulator();
        $manipulator->shouldReceive('detachRelatedRecordsById')->once()
            ->with($parent, 'included', [ 1 ])
            ->andReturn(true);

        $adapter = $this->getMockAdapter();
        $adapter->shouldReceive('dataKeyForInclude')->once()->with('include')->andReturn('included');

        $store->setManipulator($manipulator);
        $store->setResourceAdapter($adapter);

        static::assertTrue($store->detachRelatedRecordsById($parent, 'include', [ 1 ]));
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
     * @return Mockery\MockInterface|Mockery\Mock|IncludeResolverInterface
     */
    protected function getMockIncludeResolver()
    {
        return Mockery::mock(IncludeResolverInterface::class);
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

    /**
     * @return Mockery\MockInterface|Mockery\Mock|IncludeDecoratorInterface
     */
    protected function getMockIncludeDecorator()
    {
        return Mockery::mock(IncludeDecoratorInterface::class);
    }

}
