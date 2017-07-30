<?php
namespace Czim\DataStore\Resource\JsonApi;

use Czim\DataStore\Contracts\Resource\ResourceAdapterInterface;
use Czim\JsonApi\Contracts\Resource\EloquentResourceInterface;

class JsonApiEloquentResourceAdapter implements ResourceAdapterInterface
{

    /**
     * @var EloquentResourceInterface
     */
    protected $resource;


    /**
     * @param EloquentResourceInterface $resource
     */
    public function __construct(EloquentResourceInterface $resource)
    {
        $this->resource = $resource;
    }


    /**
     * Returns the data storage key to use for a given incoming attribute.
     *
     * @param string $attribute
     * @return string
     */
    public function dataKeyForAttribute($attribute)
    {
        return $this->resource->getModelAttributeForApiAttribute($attribute);
    }

    /**
     * Returns available include keys.
     *
     * @return string[]
     */
    public function availableIncludeKeys()
    {
        return $this->resource->availableIncludes();
    }

    /**
     * Returns default include keys.
     *
     * @return string[]
     */
    public function defaultIncludes()
    {
        return $this->resource->defaultIncludes();
    }

    /**
     * Returns whether included relation is for a singular relationship.
     *
     * @param string $include
     * @return bool
     */
    public function isIncludeSingular($include)
    {
        return $this->resource->isRelationshipSingular($include);
    }

    /**
     * Returns the available filter keys.
     *
     * @return string[]
     */
    public function availableFilterKeys()
    {
        return $this->resource->availableFilters();
    }

    /**
     * Returns the default filters.
     *
     * @return array    associative by filter keys.
     */
    public function defaultFilters()
    {
        return $this->resource->defaultFilters();
    }

    /**
     * Returns available sorting keys (without direction).
     *
     *
     * @return string[]
     */
    public function availableSortKeys()
    {
        return $this->resource->availableSortAttributes();
    }

    /**
     * Returns default sorting keys (with direction).
     *
     * @return string[]
     */
    public function defaultSorting()
    {
        return $this->resource->defaultSortAttributes();
    }
}
