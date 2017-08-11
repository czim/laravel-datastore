<?php
namespace Czim\DataStore\Test\Unit\Stores\Filtering\Strategies;

use Czim\DataStore\Stores\Filtering\Strategies\LikeStrategy;
use Czim\DataStore\Test\TestCase;
use Illuminate\Database\Query\Builder;
use Mockery;

class LikeStrategyTest extends TestCase
{

    /**
     * @test
     */
    function it_applies_filter_to_a_query()
    {
        $strategy = new LikeStrategy;

        /** @var Mockery\MockInterface|Builder $query */
        $query = Mockery::mock(Builder::class);
        $query->shouldReceive('where')->once()->with('testcolumn', 'like', '%value%')->andReturnSelf();

        $strategy->apply($query, 'testcolumn', 'value');
    }

}
