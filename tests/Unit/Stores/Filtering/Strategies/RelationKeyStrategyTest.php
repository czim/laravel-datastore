<?php
namespace Czim\DataStore\Test\Unit\Stores\Filtering\Strategies;

use Czim\DataStore\Stores\Filtering\Strategies\RelationKeyStrategy;
use Czim\DataStore\Test\Helpers\Models\TestModel;
use Czim\DataStore\Test\TestCase;

class RelationKeyStrategyTest extends TestCase
{

    /**
     * @test
     */
    function it_applies_filter_to_a_query_for_a_single_key()
    {
        $strategy = new RelationKeyStrategy();

        $query = TestModel::query();

        static::assertSame($query, $strategy->apply($query, 'testRelatedModels', 'value'));

        $sql      = $query->toSql();
        $bindings = $query->getBindings();

        static::assertRegExp('#\(select \* from [`\'"]test_related_models[`\'"] where [`\'"]test_models[`\'"].[`\'"]id[`\'"] = [`\'"]test_related_models[`\'"].[`\'"]test_model_id[`\'"] and [`\'"]id[`\'"] = \?\)#', $sql, 'Query does not match');
        static::assertEquals(['value'], $bindings, 'Bindings do not match');
    }

    /**
     * @test
     */
    function it_applies_filter_to_a_query_for_multiple_keys()
    {
        $strategy = new RelationKeyStrategy();

        $query = TestModel::query();

        static::assertSame($query, $strategy->apply($query, 'testRelatedModels', ['value', 'another']));

        $sql      = $query->toSql();
        $bindings = $query->getBindings();

        static::assertRegExp('#\(select \* from [`\'"]test_related_models[`\'"] where [`\'"]test_models[`\'"].[`\'"]id[`\'"] = [`\'"]test_related_models[`\'"].[`\'"]test_model_id[`\'"] and [`\'"]id[`\'"] in\s*\(\?\,\s*\?\)#i', $sql, 'Query does not match');
        static::assertEquals(['value', 'another'], $bindings, 'Bindings do not match');
    }

}
