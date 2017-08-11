<?php
namespace Czim\DataStore\Test\Unit\Stores\Filtering;

use Czim\DataStore\Contracts\Stores\Sorting\SortStrategyInterface;
use Czim\DataStore\Stores\Sorting\SortStrategyFactory;
use Czim\DataStore\Test\TestCase;
use Mockery;

class SortStrategyFactoryTest extends TestCase
{

    /**
     * @test
     */
    function it_makes_a_default_strategy_for_an_empty_strategy()
    {
        app('config')->set('datastore.sort.default', 'test');
        app('config')->set('datastore.sort.class-map.mysql', [
            'test' => static::class,
        ]);

        $factory = new SortStrategyFactory;

        $instance = Mockery::mock(SortStrategyInterface::class);

        app()->instance(static::class, $instance);

        static::assertSame($instance, $factory->make(null));
    }

    /**
     * @test
     */
    function it_makes_a_specific_strategy_for_the_default_driver()
    {
        app('config')->set('datastore.sort.class-map.mysql', [
            'test' => static::class,
        ]);

        $factory = new SortStrategyFactory;

        $instance = Mockery::mock(SortStrategyInterface::class);

        app()->instance(static::class, $instance);

        static::assertSame($instance, $factory->make('test'));
    }

    /**
     * @test
     */
    function it_makes_a_specific_strategy_for_a_specific_driver()
    {
        app('config')->set('datastore.sort.class-map.testing', [
            'test' => static::class,
        ]);

        $factory = new SortStrategyFactory;

        static::assertSame($factory, $factory->driver('testing'));

        $instance = Mockery::mock(SortStrategyInterface::class);

        app()->instance(static::class, $instance);

        static::assertSame($instance, $factory->make('test'));
    }
    
    /**
     * @test
     */
    function it_falls_back_to_a_strategy_if_none_set_for_specific_driver()
    {
        app('config')->set('datastore.sort.class-map-default', [
            'test' => static::class,
        ]);

        $factory = new SortStrategyFactory;

        static::assertSame($factory, $factory->driver('testing'));

        $instance = Mockery::mock(SortStrategyInterface::class);

        app()->instance(static::class, $instance);

        static::assertSame($instance, $factory->make('test'));
    }

}
