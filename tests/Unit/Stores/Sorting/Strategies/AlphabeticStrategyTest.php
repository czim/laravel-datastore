<?php
namespace Czim\DataStore\Test\Unit\Stores\Sorting\Strategies;

use Czim\DataStore\Stores\Sorting\Strategies\AlphabeticStrategy;
use Czim\DataStore\Test\TestCase;
use Illuminate\Database\Query\Builder;
use Mockery;

class AlphabeticStrategyTest extends TestCase
{

    /**
     * @test
     */
    function it_applies_filter_to_a_query()
    {
        $strategy = new AlphabeticStrategy;

        /** @var Mockery\MockInterface|Builder $query */
        $query = Mockery::mock(Builder::class);
        $query->shouldReceive('orderBy')->once()->with('testcolumn', 'desc')->andReturnSelf();

        $strategy->apply($query, 'testcolumn', true);
    }

}
