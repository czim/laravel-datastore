<?php
namespace Czim\DataStore\Resource\JsonApi;

use Czim\DataStore\Context\SortKey;
use Czim\DataStore\Test\TestCase;
use Czim\JsonApi\Contracts\Resource\EloquentResourceInterface;
use Mockery;

class JsonApiEloquentAdapterTest extends TestCase
{

    /**
     * @test
     */
    function it_returns_data_key_for_attribute()
    {
        $resource = $this->getMockResource();

        $resource->shouldReceive('getModelAttributeForApiAttribute')->once()->with('testing')->andReturn('result');

        $adapter = new JsonApiEloquentResourceAdapter($resource);

        static::assertEquals('result', $adapter->dataKeyForAttribute('testing'));
    }

    /**
     * @test
     */
    function it_returns_data_key_for_include()
    {
        $resource = $this->getMockResource();

        $resource->shouldReceive('getRelationMethodForInclude')->once()->with('testing')->andReturn('result');

        $adapter = new JsonApiEloquentResourceAdapter($resource);

        static::assertEquals('result', $adapter->dataKeyForInclude('testing'));
    }

    /**
     * @test
     */
    function it_returns_available_includes()
    {
        $resource = $this->getMockResource();

        $resource->shouldReceive('availableIncludes')->once()->andReturn(['test']);

        $adapter = new JsonApiEloquentResourceAdapter($resource);

        static::assertEquals(['test'], $adapter->availableIncludeKeys());
    }

    /**
     * @test
     */
    function it_returns_default_includes()
    {
        $resource = $this->getMockResource();

        $resource->shouldReceive('defaultIncludes')->once()->andReturn(['test']);

        $adapter = new JsonApiEloquentResourceAdapter($resource);

        static::assertEquals(['test'], $adapter->defaultIncludes());
    }

    /**
     * @test
     */
    function it_returns_whether_include_is_singular()
    {
        $resource = $this->getMockResource();

        $resource->shouldReceive('isRelationshipSingular')->once()->with('testing')->andReturn(true);

        $adapter = new JsonApiEloquentResourceAdapter($resource);

        static::assertTrue($adapter->isIncludeSingular('testing'));
    }

    /**
     * @test
     */
    function it_returns_available_filters()
    {
        $resource = $this->getMockResource();

        $resource->shouldReceive('availableFilters')->once()->andReturn(['test']);

        $adapter = new JsonApiEloquentResourceAdapter($resource);

        static::assertEquals(['test'], $adapter->availableFilterKeys());
    }

    /**
     * @test
     */
    function it_returns_default_filters()
    {
        $resource = $this->getMockResource();

        $resource->shouldReceive('defaultFilters')->once()->andReturn(['test']);

        $adapter = new JsonApiEloquentResourceAdapter($resource);

        static::assertEquals(['test'], $adapter->defaultFilters());
    }

    /**
     * @test
     */
    function it_returns_available_sortkeys()
    {
        $resource = $this->getMockResource();

        $resource->shouldReceive('availableSortAttributes')->once()->andReturn(['test']);

        $adapter = new JsonApiEloquentResourceAdapter($resource);

        static::assertEquals(['test'], $adapter->availableSortKeys());
    }

    /**
     * @test
     * @uses \Czim\DataStore\Context\SortKey
     */
    function it_returns_default_sortkeys()
    {
        $resource = $this->getMockResource();

        $sorts = [
            new SortKey('test', true),
        ];

        $resource->shouldReceive('defaultSortAttributes')->once()->andReturn($sorts);

        $adapter = new JsonApiEloquentResourceAdapter($resource);

        static::assertEquals($sorts, $adapter->defaultSorting());
    }

    /**
     * @test
     */
    function it_returns_default_sortkeys_making_sortkey_instances_as_required()
    {
        $resource = $this->getMockResource();

        $resource->shouldReceive('defaultSortAttributes')->once()->andReturn(['test']);

        $adapter = new JsonApiEloquentResourceAdapter($resource);

        static::assertEquals(['test'], $adapter->defaultSorting());
    }


    /**
     * @return Mockery\Mock|Mockery\MockInterface|EloquentResourceInterface
     */
    protected function getMockResource()
    {
        return Mockery::mock(EloquentResourceInterface::class);
    }

}
