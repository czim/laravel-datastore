<?php
namespace Czim\DataStore\Test\Unit\Stores\Filtering\Strategies;

use Czim\DataStore\Stores\Filtering\Strategies\ExactCommaSeparatedStrategy;
use Czim\DataStore\Test\TestCase;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Query\Builder;
use Mockery;

class ExactCommaSeparatedStrategyTest extends TestCase
{

    /**
     * @test
     */
    function it_applies_filter_to_a_query()
    {
        $strategy = new ExactCommaSeparatedStrategy;

        /** @var Mockery\MockInterface|Builder $query */
        $query = Mockery::mock(Builder::class);
        $query->shouldReceive('whereIn')->once()->with('testcolumn', ['value', 'another'])->andReturnSelf();

        $strategy->apply($query, 'testcolumn', ['value', 'another']);
    }

    /**
     * @test
     */
    function it_applies_filter_to_a_query_for_a_comma_separated_string()
    {
        $strategy = new ExactCommaSeparatedStrategy;

        /** @var Mockery\MockInterface|Builder $query */
        $query = Mockery::mock(Builder::class);
        $query->shouldReceive('whereIn')->once()->with('testcolumn', ['value', 'another'])->andReturnSelf();

        $strategy->apply($query, 'testcolumn', 'value,another');
    }

    /**
     * @test
     */
    function it_applies_filter_to_a_query_for_an_arrayable_object()
    {
        $strategy = new ExactCommaSeparatedStrategy;

        $mock = Mockery::mock(Arrayable::class);
        $mock->shouldReceive('toArray')->once()->andReturn(['value', 'another']);

        /** @var Mockery\MockInterface|Builder $query */
        $query = Mockery::mock(Builder::class);
        $query->shouldReceive('whereIn')->once()->with('testcolumn', ['value', 'another'])->andReturnSelf();

        $strategy->apply($query, 'testcolumn', $mock);
    }

}
