<?php
namespace Czim\DataStore\Test\Unit\Stores\Filtering\Strategies\Postgres;

use Czim\DataStore\Stores\Filtering\Strategies\LikeStrategy;
use Czim\DataStore\Stores\Filtering\Strategies\Postgres\LikeCaseInsensitiveStrategy;
use Czim\DataStore\Test\TestCase;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Expression;
use Mockery;

class LikeCaseInsensitiveStrategyTest extends TestCase
{

    /**
     * @test
     */
    function it_applies_filter_to_a_query()
    {
        $strategy = new LikeCaseInsensitiveStrategy;

        /** @var Mockery\MockInterface|Builder $query */
        $query = Mockery::mock(Builder::class);
        $query->shouldReceive('where')->once()
            ->with(
                Mockery::on(function ($column) {
                    return $column instanceof Expression && $column->getValue() == 'lower(testcolumn)';
                }),
                'like',
                '%value%'
            )
            ->andReturnSelf();

        $strategy->apply($query, 'testcolumn', 'Value');
    }

}
