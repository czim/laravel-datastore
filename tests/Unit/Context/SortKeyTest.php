<?php
namespace Czim\DataStore\Test\Unit\Context;

use Czim\DataStore\Context\SortKey;
use Czim\DataStore\Test\TestCase;

class SortKeyTest extends TestCase
{

    /**
     * @test
     */
    function it_can_be_constructed_with_key_and_direction()
    {
        $key = new SortKey('test', false);

        static::assertEquals('test', $key->getKey());
        static::assertFalse($key->isReversed());

        $key = new SortKey('test', true);

        static::assertTrue($key->isReversed());
    }

    /**
     * @test
     */
    function it_returns_sort_direction()
    {
        $key = new SortKey('test', false);

        static::assertEquals('test', $key->getKey());
        static::assertEquals('asc', $key->getDirection());

        $key = new SortKey('test', true);

        static::assertEquals('desc', $key->getDirection());
    }

}
