<?php
namespace Czim\DataStore\Test\Unit\Context;

use Czim\DataStore\Context\RequestContext;
use Czim\DataStore\Context\SortKey;
use Czim\DataStore\Test\TestCase;

class RequestContextTest extends TestCase
{

    /**
     * @test
     */
    function it_returns_all_filters()
    {
        $context = new RequestContext;

        static::assertSame([], $context->filters(), 'Unset filters should return empty array');

        $context->filters = ['test' => 'filter'];

        static::assertEquals(['test' => 'filter'], $context->filters());
    }

    /**
     * @test
     */
    function it_returns_a_specific_filter_value()
    {
        $context = new RequestContext;

        static::assertNull($context->filter('test'));

        $context->filters = ['test' => 'abc'];

        static::assertEquals('abc', $context->filter('test'));
    }

    /**
     * @test
     */
    function it_returns_whether_pagination_should_be_applied()
    {
        $context = new RequestContext;

        $context->standard_pagination = false;
        $context->cursor_pagination   = false;
        static::assertFalse($context->shouldBePaginated());
        static::assertFalse($context->shouldBeCursorPaginated());

        $context->standard_pagination = true;
        static::assertTrue($context->shouldBePaginated());

        // It should also paginate if all pagination properties are set
        $context->standard_pagination = false;
        $context->page_number = 2;
        $context->page_size   = 10;
        static::assertTrue($context->shouldBePaginated());

        // But not when cursor pagination is enabled
        $context->cursor_pagination = true;
        static::assertFalse($context->shouldBePaginated());

        // But then it should return that it is cursor paginated
        static::assertTrue($context->shouldBeCursorPaginated());

        // It should also paginate if cursor pagination properties are set
        $context->cursor_pagination = false;
        $context->page_number = null;
        $context->page_size   = null;
        $context->page_cursor = '2017-01-01';
        static::assertTrue($context->shouldBeCursorPaginated());
    }

    /**
     * @test
     */
    function it_returns_page_properties()
    {
        $context = new RequestContext;

        static::assertNull($context->pageNumber());
        static::assertNull($context->pageSize());
        static::assertNull($context->pageLimit());
        static::assertNull($context->pageCursor());
        static::assertNull($context->pageOffset());

        $context->page_number = 1;
        $context->page_size   = 10;
        $context->page_limit  = 15;
        $context->page_cursor = '123';
        $context->page_offset = 300;

        static::assertEquals(1, $context->pageNumber());
        static::assertEquals(10, $context->pageSize());
        static::assertEquals(15, $context->pageLimit());
        static::assertEquals('123', $context->pageCursor());
        static::assertEquals(300, $context->pageOffset());
    }

    /**
     * @test
     * @uses \Czim\DataStore\Context\SortKey
     */
    function it_returns_sort_attributes()
    {
        $context = new RequestContext;

        static::assertSame([], $context->sorting(), 'Unset sort should return empty array');

        $context->sorting = ['-test', 'testing'];

        $sorting = $context->sorting();

        static::assertIsArray($sorting);
        static::assertCount(2, $sorting);
        static::assertInstanceOf(SortKey::class, $sorting[0]);
        static::assertEquals('test', $sorting[0]->getKey());
        static::assertTrue($sorting[0]->isReversed());
        static::assertInstanceOf(SortKey::class, $sorting[1]);
        static::assertEquals('testing', $sorting[1]->getKey());
        static::assertFalse($sorting[1]->isReversed());
    }

    /**
     * @test
     * @uses \Czim\DataStore\Context\SortKey
     */
    function it_returns_sort_attributes_when_already_cast_as_sortkey_instances()
    {
        $context = new RequestContext;

        static::assertSame([], $context->sorting(), 'Unset sort should return empty array');

        $context->sorting = [ new SortKey('test', true) ];

        $sorting = $context->sorting();

        static::assertIsArray($sorting);
        static::assertCount(1, $sorting);
        static::assertInstanceOf(SortKey::class, $sorting[0]);
        static::assertEquals('test', $sorting[0]->getKey());
        static::assertTrue($sorting[0]->isReversed());
    }

}
