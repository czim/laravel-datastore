<?php
namespace Czim\DataStore\Test\Unit\Stores\Sorting\Strategies\Postgres;

use Czim\DataStore\Stores\Sorting\Strategies\Postgres\AlphabeticStrategy;
use Czim\DataStore\Test\TestCase;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Expression;
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
        $query->shouldReceive('orderBy')->once()
            ->with(
                Mockery::on(function ($column) {
                    return $column instanceof Expression && $column->getValue() == 'lower(testcolumn)';
                }),
                'desc'
            )
            ->andReturnSelf();

        $strategy->apply($query, 'testcolumn', true);
    }

}
