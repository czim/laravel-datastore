<?php
namespace Czim\DataStore\Test\Unit\Stores\Filtering;

use Czim\DataStore\Contracts\Resource\ResourceAdapterInterface;
use Czim\DataStore\Contracts\Stores\Filtering\FilterStrategyFactoryInterface;
use Czim\DataStore\Contracts\Stores\Filtering\FilterStrategyInterface;
use Czim\DataStore\Stores\Filtering\Data\DefaultFilterData;
use Czim\DataStore\Stores\Filtering\DefaultFilter;
use Czim\DataStore\Test\Helpers\Models\TestModel;
use Czim\DataStore\Test\TestCase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Mockery;

class DefaultFilterTest extends TestCase
{

    /**
     * @test
     */
    function it_takes_a_model_instance()
    {
        $filter = new DefaultFilter;

        static::assertSame($filter, $filter->setModel(new TestModel));
    }

    /**
     * @test
     */
    function it_takes_a_resource_adapter()
    {
        $filter = new DefaultFilter;

        static::assertSame($filter, $filter->setResourceAdapter($this->getMockAdapter()));
    }

    /**
     * @test
     */
    function it_takes_a_filter_strategy_factory()
    {
        $filter = new DefaultFilter;

        static::assertSame($filter, $filter->setStrategyFactory($this->getMockStrategyFactory()));
    }

    /**
     * @test
     */
    function it_accepts_filter_data_as_an_array_along_with_available_keys_as_defaults()
    {
        $filter = new DefaultFilter;

        static::assertSame($filter, $filter->setData(['some' => 'value'], ['some', 'keys']));

        $data = $filter->getFilterData();
        static::assertEquals(['some' => 'value', 'keys' => null], $data->getAttributes());
        static::assertEquals(['some' => null, 'keys' => null], $data->getDefaults());
    }

    /**
     * @test
     */
    function it_accepts_filter_data_as_an_array_without_defaults_defaulting_to_data_keys_if_no_resource_adapter_given()
    {
        $filter = new DefaultFilter;

        static::assertSame($filter, $filter->setData(['some' => 'value']));

        $data = $filter->getFilterData();
        static::assertEquals(['some' => 'value'], $data->getAttributes());
        static::assertEquals(['some' => null], $data->getDefaults());
    }

    /**
     * @test
     */
    function it_accepts_filter_data_as_an_array_without_defaults_defaulting_using_resource_adapter()
    {
        $filter = new DefaultFilter;

        $adapter = $this->getMockAdapter();
        $adapter->shouldReceive('availableFilterKeys')->once()->andReturn(['some', 'keys']);
        $filter->setResourceAdapter($adapter);

        static::assertSame($filter, $filter->setData(['some' => 'value']));

        $data = $filter->getFilterData();
        static::assertEquals(['some' => 'value', 'keys' => null], $data->getAttributes());
        static::assertEquals(['some' => null, 'keys' => null], $data->getDefaults());
    }
    
    /**
     * @test
     */
    function it_applies_a_strategy_for_a_filtered_attribute_parameter()
    {
        $filter = new DefaultFilter;
        $filter->setModel(new TestModel);
        $filter->setFilterData(new DefaultFilterData(['some' => 'value'], ['some' => null]));

        $adapter = $this->getMockAdapter();
        $adapter->shouldReceive('availableIncludeKeys')->atleast()->once()->andReturn(['something_else']);
        $adapter->shouldReceive('dataKeyForAttribute')->once()->with('some')->andReturn('some_resolved');
        $filter->setResourceAdapter($adapter);

        $query = $this->getMockQueryBuilder();

        $strategy = $this->getMockStrategy();
        $strategy->shouldReceive('apply')
            ->once()
            ->with($query, 'some_resolved', 'value')
            ->andReturnUsing(function ($query) {
                /** @var Builder $query */
                return $query;
            });

        $factory = $this->getMockStrategyFactory();
        $factory->shouldReceive('make')->once()->with('like', false)->andReturn($strategy);
        $filter->setStrategyFactory($factory);

        static::assertSame($query, $filter->apply($query));
    }

    /**
     * @test
     */
    function it_applies_a_strategy_for_a_filtered_attribute_parameter_directly_when_no_resource_adapter_is_set()
    {
        $filter = new DefaultFilter;
        $filter->setFilterData(new DefaultFilterData(['some' => 'value'], ['some' => null]));
        $filter->setModel(new TestModel);

        $query = $this->getMockQueryBuilder();

        $strategy = $this->getMockStrategy();
        $strategy->shouldReceive('apply')
            ->once()
            ->with($query, 'some', 'value')
            ->andReturnUsing(function ($query) {
                /** @var Builder $query */
                return $query;
            });

        $factory = $this->getMockStrategyFactory();
        $factory->shouldReceive('make')->once()->with('like', false)->andReturn($strategy);
        $filter->setStrategyFactory($factory);



        static::assertSame($query, $filter->apply($query));
    }

    /**
     * @test
     * @expectedException \RuntimeException
     */
    function it_throws_an_exception_when_attempting_to_apply_a_strategy_without_a_factory_set()
    {
        $filter = new DefaultFilter;
        $filter->setFilterData(new DefaultFilterData(['some' => 'value'], ['some' => null]));
        $filter->setModel(new TestModel);

        $adapter = $this->getMockAdapter();
        $adapter->shouldReceive('availableIncludeKeys')->atLeast()->once()->andReturn(['something_else']);
        $adapter->shouldReceive('dataKeyForAttribute')->once()->with('some')->andReturn('some_resolved');
        $filter->setResourceAdapter($adapter);

        $query = $this->getMockQueryBuilder();

        $filter->apply($query);
    }

    /**
     * @test
     * @expectedException \Czim\Filter\Exceptions\FilterParameterUnhandledException
     */
    function it_falls_back_to_default_filter_behavior_if_no_key_could_be_resolved_for_a_parameter()
    {
        $filter = new DefaultFilter;
        $filter->setFilterData(new DefaultFilterData(['some' => 'value'], ['some' => null]));

        $adapter = $this->getMockAdapter();
        $adapter->shouldReceive('availableIncludeKeys')->once()->andReturn(['something_else']);
        $adapter->shouldReceive('dataKeyForAttribute')->once()->with('some')->andReturn(null);
        $filter->setResourceAdapter($adapter);

        $query = $this->getMockQueryBuilder();

        $filter->apply($query);
    }

    /**
     * @test
     */
    function it_applies_a_strategy_as_reversed_when_the_key_is_prefixed_as_configured()
    {
        $this->app['config']->set('datastore.filter.reverse-key-prefix', '-');

        $filter = new DefaultFilter;
        $filter->setModel(new TestModel);
        $filter->setFilterData(new DefaultFilterData(['-some' => 'value'], ['-some' => null]));

        $adapter = $this->getMockAdapter();
        $adapter->shouldReceive('availableIncludeKeys')->atleast()->once()->andReturn(['something_else']);
        $adapter->shouldReceive('dataKeyForAttribute')->once()->with('some')->andReturn('some_resolved');
        $filter->setResourceAdapter($adapter);

        $query = $this->getMockQueryBuilder();

        $strategy = $this->getMockStrategy();
        $strategy->shouldReceive('apply')
            ->once()
            ->with($query, 'some_resolved', 'value')
            ->andReturnUsing(function ($query) {
                /** @var Builder $query */
                return $query;
            });

        $factory = $this->getMockStrategyFactory();
        $factory->shouldReceive('make')->once()->with('like', true)->andReturn($strategy);
        $filter->setStrategyFactory($factory);

        static::assertSame($query, $filter->apply($query));
    }

    // ------------------------------------------------------------------------------
    //      Include filtering
    // ------------------------------------------------------------------------------

    /**
     * @test
     */
    function it_applies_a_strategy_for_a_filtered_include_parameter()
    {
        $this->app['config']->set('datastore.filter.default-relation-strategies.' . HasMany::class, 'relation-filter-hasmany');

        $filter = new DefaultFilter;
        $filter->setFilterData(new DefaultFilterData(['some' => 'value'], ['some' => null]));
        $filter->setModel(new TestModel);

        $adapter = $this->getMockAdapter();
        $adapter->shouldReceive('availableIncludeKeys')->atLeast()->once()->andReturn(['some']);
        $adapter->shouldReceive('dataKeyForInclude')->atLeast()->once()->with('some')->andReturn('testRelatedModels');
        $filter->setResourceAdapter($adapter);

        $query = $this->getMockQueryBuilder();

        $strategy = $this->getMockStrategy();
        $strategy->shouldReceive('apply')
            ->once()
            ->with($query, 'testRelatedModels', 'value')
            ->andReturnUsing(function ($query) {
                /** @var Builder $query */
                return $query;
            });
        $this->app->instance('relation-filter-hasmany', $strategy);

        $factory = $this->getMockStrategyFactory();
        $factory->shouldReceive('make')->once()->with('relation-filter-hasmany', false)->andReturn($strategy);
        $filter->setStrategyFactory($factory);

        static::assertSame($query, $filter->apply($query));
    }

    /**
     * @test
     */
    function it_applies_a_strategy_for_a_filtered_include_parameter_falling_back_to_default()
    {
        $this->app['config']->set('datastore.filter.default', 'relation-filter-hasmany');

        $filter = new DefaultFilter;
        $filter->setFilterData(new DefaultFilterData(['some' => 'value'], ['some' => null]));
        $filter->setModel(new TestModel);

        $adapter = $this->getMockAdapter();
        $adapter->shouldReceive('availableIncludeKeys')->atLeast()->once()->andReturn(['some']);
        $adapter->shouldReceive('dataKeyForInclude')->atLeast()->once()->with('some')->andReturn('testRelatedModels');
        $filter->setResourceAdapter($adapter);

        $query = $this->getMockQueryBuilder();

        $strategy = $this->getMockStrategy();
        $strategy->shouldReceive('apply')
            ->once()
            ->with($query, 'testRelatedModels', 'value')
            ->andReturnUsing(function ($query) {
                /** @var Builder $query */
                return $query;
            });
        $this->app->instance('relation-filter-hasmany', $strategy);

        $factory = $this->getMockStrategyFactory();
        $factory->shouldReceive('make')->once()->with('relation-filter-hasmany', false)->andReturn($strategy);
        $filter->setStrategyFactory($factory);

        static::assertSame($query, $filter->apply($query));
    }

    /**
     * @test
     * @expectedException \UnexpectedValueException
     */
    function it_throws_an_exception_applying_a_filtered_include_parameter_that_has_no_matching_relation_method()
    {
        $filter = new DefaultFilter;
        $filter->setFilterData(new DefaultFilterData(['some' => 'value'], ['some' => null]));
        $filter->setModel(new TestModel);

        $adapter = $this->getMockAdapter();
        $adapter->shouldReceive('availableIncludeKeys')->atLeast()->once()->andReturn(['some']);
        $adapter->shouldReceive('dataKeyForInclude')->atLeast()->once()->with('some')->andReturn('does_not_exist');
        $filter->setResourceAdapter($adapter);

        $query = $this->getMockQueryBuilder();

        $filter->apply($query);
    }

    /**
     * @test
     * @expectedException \UnexpectedValueException
     */
    function it_throws_an_exception_applying_a_filtered_include_parameter_that_has_a_non_relation_method()
    {
        $filter = new DefaultFilter;
        $filter->setFilterData(new DefaultFilterData(['some' => 'value'], ['some' => null]));
        $filter->setModel(new TestModel);

        $adapter = $this->getMockAdapter();
        $adapter->shouldReceive('availableIncludeKeys')->atLeast()->once()->andReturn(['some']);
        $adapter->shouldReceive('dataKeyForInclude')->atLeast()->once()->with('some')->andReturn('notARelation');
        $filter->setResourceAdapter($adapter);

        $query = $this->getMockQueryBuilder();

        $filter->apply($query);
    }

    /**
     * @return Mockery\MockInterface|Mockery\Mock|ResourceAdapterInterface
     */
    protected function getMockAdapter()
    {
        return Mockery::mock(ResourceAdapterInterface::class);
    }

    /**
     * @return \Mockery\MockInterface|Mockery\Mock|FilterStrategyFactoryInterface
     */
    protected function getMockStrategyFactory()
    {
        return Mockery::mock(FilterStrategyFactoryInterface::class);
    }

    /**
     * @return \Mockery\MockInterface|Mockery\Mock|FilterStrategyInterface
     */
    protected function getMockStrategy()
    {
        return Mockery::mock(FilterStrategyInterface::class);
    }

    /**
     * @return \Mockery\MockInterface|Mockery\Mock|Builder
     */
    protected function getMockQueryBuilder()
    {
        return Mockery::mock(Builder::class);
    }

}
