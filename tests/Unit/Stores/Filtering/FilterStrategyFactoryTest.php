<?php
namespace Czim\DataStore\Test\Unit\Stores\Filtering;

use Czim\DataStore\Contracts\Stores\Filtering\FilterStrategyInterface;
use Czim\DataStore\Stores\Filtering\FilterStrategyFactory;
use Czim\DataStore\Test\TestCase;
use Mockery;

class FilterStrategyFactoryTest extends TestCase
{

    /**
     * @test
     */
    function it_makes_a_default_strategy_for_an_empty_strategy()
    {
        app('config')->set('datastore.filter.default', 'test');
        app('config')->set('datastore.filter.class-map.mysql', [
            'test' => static::class,
        ]);

        $factory = new FilterStrategyFactory;

        $instance = Mockery::mock(FilterStrategyInterface::class);

        app()->instance(static::class, $instance);

        static::assertSame($instance, $factory->make(null));
    }

    /**
     * @test
     */
    function it_makes_a_specific_strategy_for_the_default_driver()
    {
        app('config')->set('datastore.filter.class-map.mysql', [
            'test' => static::class,
        ]);

        $factory = new FilterStrategyFactory;

        $instance = Mockery::mock(FilterStrategyInterface::class);

        app()->instance(static::class, $instance);

        static::assertSame($instance, $factory->make('test'));
    }

    /**
     * @test
     */
    function it_makes_a_specific_strategy_for_a_specific_driver()
    {
        app('config')->set('datastore.filter.class-map.testing', [
            'test' => static::class,
        ]);

        $factory = new FilterStrategyFactory;

        static::assertSame($factory, $factory->driver('testing'));

        $instance = Mockery::mock(FilterStrategyInterface::class);

        app()->instance(static::class, $instance);

        static::assertSame($instance, $factory->make('test'));
    }
    
    /**
     * @test
     */
    function it_falls_back_to_a_strategy_if_none_set_for_specific_driver()
    {
        app('config')->set('datastore.filter.class-map-default', [
            'test' => static::class,
        ]);

        $factory = new FilterStrategyFactory;

        static::assertSame($factory, $factory->driver('testing'));

        $instance = Mockery::mock(FilterStrategyInterface::class);

        app()->instance(static::class, $instance);

        static::assertSame($instance, $factory->make('test'));
    }

    /**
     * @test
     */
    function it_applies_reverse_to_instance_when_given_as_parameter()
    {
        app('config')->set('datastore.filter.default', 'test');
        app('config')->set('datastore.filter.class-map.mysql', [
            'test' => static::class,
        ]);

        $factory = new FilterStrategyFactory;

        $instance = Mockery::mock(FilterStrategyInterface::class);

        $instance->shouldReceive('setReversed')->atLeast()->once();

        app()->instance(static::class, $instance);

        static::assertSame($instance, $factory->make(null, true));
    }

}
