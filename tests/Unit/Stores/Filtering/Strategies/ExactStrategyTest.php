<?php
namespace Czim\DataStore\Test\Unit\Stores\Filtering\Strategies;

use Czim\DataStore\Stores\Filtering\Strategies\ExactStrategy;
use Czim\DataStore\Test\TestCase;
use Illuminate\Database\Query\Builder;
use Mockery;

class ExactStrategyTest extends TestCase
{

    /**
     * @test
     */
    function it_applies_filter_to_a_query()
    {
        $strategy = new ExactStrategy;

        /** @var Mockery\MockInterface|Builder $query */
        $query = Mockery::mock(Builder::class);
        $query->shouldReceive('where')->once()->with('testcolumn', '=', 'value')->andReturnSelf();

        $strategy->apply($query, 'testcolumn', 'value');
    }

    /**
     * @test
     */
    function it_applies_filter_to_a_query_for_an_array_value()
    {
        $strategy = new ExactStrategy;

        /** @var Mockery\MockInterface|Builder $query */
        $query = Mockery::mock(Builder::class);
        $query->shouldReceive('whereIn')->once()->with('testcolumn', ['value', 'another'])->andReturnSelf();

        $strategy->apply($query, 'testcolumn', ['value', 'another']);
    }

}
