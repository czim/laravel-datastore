<?php
namespace Czim\DataStore\Resource\JsonApi;

use Czim\DataStore\Context\SortKey;
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
     * Returns the data storage key to use for a given presentation attribute.
     *
     * For an Eloquent model, this would be the corresponding model attribute.
     *
     * @param string $attribute
     * @return string
     */
    public function dataKeyForAttribute($attribute)
    {
        return $this->resource->getModelAttributeForApiAttribute($attribute);
    }

    /**
     * Returns the data storage key to use for a given presentation include.
     *
     * For an Eloquent model, this would be the corresponding relation method name.
     *
     * @param string $include
     * @return string
     */
    public function dataKeyForInclude($include)
    {
        return $this->resource->getRelationMethodForInclude($include);
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
     * @return string[]
     */
    public function availableSortKeys()
    {
        return $this->resource->availableSortAttributes();
    }

    /**
     * Returns default sorting keys (with direction).
     *
     * @return SortKey[]
     */
    public function defaultSorting()
    {
        return array_map(
            function ($sort) {
                if ($sort instanceof SortKey) {
                    return $sort;
                }
                return new SortKey(ltrim($sort, '-'), substr($sort, 0, 1) == '-');
            },
            $this->resource->defaultSortAttributes()
        );
    }

}
