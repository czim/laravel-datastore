<?php
namespace Czim\DataStore\Test\Unit\Stores\Filtering\Strategies\Postgres;

use Czim\DataStore\Stores\Filtering\Strategies\Postgres\ExactCaseInsensitiveStrategy;
use Czim\DataStore\Test\TestCase;
use DB;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Expression;
use Mockery;

class ExactCaseInsensitiveStrategyTest extends TestCase
{

    /**
     * @test
     */
    function it_applies_filter_to_a_query()
    {
        $strategy = new ExactCaseInsensitiveStrategy;

        /** @var Mockery\MockInterface|Builder $query */
        $query = Mockery::mock(Builder::class);
        $query->shouldReceive('where')->once()
            ->with(
                Mockery::on(function ($column) {
                    return $column instanceof Expression && $column->getValue() == 'lower(testcolumn)';
                }),
                '=',
                'value'
            )
            ->andReturnSelf();

        $strategy->apply($query, 'testcolumn', 'Value');
    }

    /**
     * @test
     */
    function it_applies_filter_to_a_query_for_an_array_value()
    {
        $strategy = new ExactCaseInsensitiveStrategy;

        /** @var Mockery\MockInterface|Builder $query */
        $query = Mockery::mock(Builder::class);
        $query->shouldReceive('whereIn')->once()
            ->with(
                Mockery::on(function ($column) {
                    return $column instanceof Expression && $column->getValue() == 'lower(testcolumn)';
                }),
                ['value', 'another']
            )
            ->andReturnSelf();

        $strategy->apply($query, 'testcolumn', ['Value', 'Another']);
    }

}
