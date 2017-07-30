<?php
namespace Czim\DataStore\Stores;

use Czim\DataStore\Contracts\Context\ContextInterface;
use Czim\DataStore\Contracts\Resource\ResourceAdapterInterface;
use Czim\DataStore\Contracts\Stores\DataStoreInterface;
use Czim\DataStore\Contracts\Stores\Filtering\FilterStrategyFactoryInterface;
use Czim\DataStore\Contracts\Stores\Sorting\SortStrategyFactoryInterface;
use Czim\DataStore\Enums\FilterStrategyEnum;
use Czim\DataStore\Enums\SortStrategyEnum;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class AbstractEloquentDataStore implements DataStoreInterface
{

    /**
     * @var ResourceAdapterInterface
     */
    protected $resourceAdapter;

    /**
     * @var string
     */
    protected $modelClass;

    /**
     * @var string
     */
    protected $strategyDriver = 'mysql';

    /**
     * The includes to apply for the next retrieval
     *
     * @var array
     */
    protected $includes = [];


    /**
     * @param ResourceAdapterInterface $resourceAdapter
     */
    public function __construct(ResourceAdapterInterface $resourceAdapter)
    {
        $this->resourceAdapter = $resourceAdapter;
    }

    /**
     * Returns data by single ID.
     *
     * @param mixed $id
     * @param array $includes
     * @return mixed
     */
    public function getById($id, $includes = [])
    {
        $this->queueIncludes($includes);

        return $this->retrieveById($id);
    }

    /**
     * Returns data by set of IDs.
     *
     * @param mixed[] $ids
     * @param array   $includes
     * @return mixed
     */
    public function getManyById(array $ids, $includes = [])
    {
        $this->queueIncludes($includes);

        return $this->retrieveManyById($ids);
    }

    /**
     * Returns data by given context.
     *
     * @param ContextInterface $context
     * @param array            $includes
     * @return mixed
     */
    public function getByContext(ContextInterface $context, $includes = [])
    {
        $this->queueIncludes($includes);

        $query = $this->retrieveQuery();

        $query = $this->applyFilters($query, $context->filters());
        $query = $this->applySorting($query, $context->sorting());

        if ($context->shouldBePaginated()) {

            $total = (clone $query)->count();
            $page  = max($context->pageNumber(), 1);
            $size  = $context->pageSize() ?: config('jsonapi.pagination.size');

            return new LengthAwarePaginator(
                $query->take($size)->skip($page - 1 * $size)->get(),
                $total,
                $size,
                $page
            );
        }

        return $query->get();
    }

    /**
     * Applies filters to a query.
     *
     * @param Builder    $query
     * @param array      $filters
     * @return Builder
     */
    protected function applyFilters($query, array $filters)
    {
        if (empty($filters) && ! empty($default = $this->resourceAdapter->defaultFilters())) {
            $filters = $default;
        }

        // Special filters? Only consider available in resource
        $available = $this->resourceAdapter->availableFilterKeys();

        $filters = array_filter($filters, function ($key) use ($available) {
            return in_array($key, $available);
        }, ARRAY_FILTER_USE_KEY);

        foreach ($filters as $key => $value) {
            $this->applyFilterValue($query, $this->determineFilterStrategyForKey($key), $key, $value);
        }

        return $query;
    }

    /**
     * Applies a single filter value to a query.
     *
     * @param Builder $query
     * @param string  $strategy
     * @param string  $key
     * @param mixed   $value
     * @return Builder
     */
    protected function applyFilterValue($query, $strategy, $key, $value)
    {
        $attribute = $this->resourceAdapter->dataKeyForAttribute($key);

        $filter = $this->makeFilterStrategyInstance($strategy);

        return $filter->apply($query, $attribute, $value);
    }

    /**
     * Returns the filter strategy alias to use for a given filter key.
     *
     * @param string $key
     * @return string
     */
    protected function determineFilterStrategyForKey($key)
    {
        return config(
            "datastore.filter.strategies.{$this->modelClass}",
            config(
                "datastore.filter.default-strategies.{$key}",
                config('datastore.filter.default', FilterStrategyEnum::LIKE)
            )
        );
    }

    /**
     * Applies sorting order to a query.
     *
     * @param Builder $query
     * @param array   $sorting
     * @return Builder
     */
    public function applySorting($query, array $sorting)
    {
        if (empty($sorting) && ! empty($default = $this->resourceAdapter->defaultSorting())) {
            $sorting = $default;
        } else {
            // Only consider available sort attributes
            $available = $this->resourceAdapter->availableSortKeys();

            $sorting = array_filter($sorting, function ($attribute) use ($available) {
                return in_array(ltrim($attribute, '-'), $available);
            });
        }

        if ([] === $sorting) {
            return $query;
        }

        foreach ($sorting as $sort) {
            $attribute = $this->resourceAdapter->dataKeyForAttribute(ltrim($sort, '-'));

            $this->applySortParameter($query, $attribute, starts_with($sort, '-'));
        }

        return $query;
    }

    /**
     * Applies a single sort parameter to a query.
     *
     * @param Builder $query
     * @param string  $attribute    Eloquent model attribute
     * @param bool    $reverse
     * @return Builder
     */
    protected function applySortParameter($query, $attribute, $reverse = false)
    {
        $strategy  = $this->determineSortStrategyForAttribute($attribute);

        $sorter = $this->makeSortStrategyInstance($strategy);

        return $sorter->apply($query, $attribute, (bool) $reverse);
    }

    /**
     * Returns the sorting strategy alias to use for a given sort attribute.
     *
     * @param string $attribute
     * @return string
     */
    protected function determineSortStrategyForAttribute($attribute)
    {
        return config(
            "datastore.sort.strategies.{$this->modelClass}",
            config(
                "datastore.sort.default-strategies.{$attribute}",
                config('datastore.sort.default', SortStrategyEnum::ALPHABETIC)
            )
        );
    }

    /**
     * Prepares datastore to eager load the given includes.
     *
     * @param array $includes
     * @return $this
     */
    protected function queueIncludes(array $includes)
    {
        $this->includes = $includes;

        return $this;
    }

    /**
     * Clears currently queued includes.
     *
     * @return $this
     */
    protected function clearIncludes()
    {
        $this->includes = [];

        return $this;
    }


    // ------------------------------------------------------------------------------
    //      Strategies
    // ------------------------------------------------------------------------------

    /**
     * @param string $strategy
     * @return \Czim\DataStore\Contracts\Stores\Filtering\FilterStrategyInterface
     */
    protected function makeFilterStrategyInstance($strategy)
    {
        /** @var FilterStrategyFactoryInterface $factory */
        $factory = app(FilterStrategyFactoryInterface::class);

        return $factory->driver($this->strategyDriver)->make($strategy);
    }

    /**
     * @param string $strategy
     * @return \Czim\DataStore\Contracts\Stores\Sorting\SortStrategyInterface
     */
    protected function makeSortStrategyInstance($strategy)
    {
        /** @var SortStrategyFactoryInterface $factory */
        $factory = app(SortStrategyFactoryInterface::class);

        return $factory->driver($this->strategyDriver)->make($strategy);
    }


    // ------------------------------------------------------------------------------
    //      Abstract
    // ------------------------------------------------------------------------------

    /**
     * Returns a model instance.
     *
     * @return Model
     */
    abstract protected function getModel();

    /**
     * Returns a fresh query builder for the model.
     *
     * @return Builder|EloquentBuilder
     */
    abstract protected function retrieveQuery();

    /**
     * Returns model by ID.
     *
     * @param mixed $id
     * @return mixed
     */
    abstract protected function retrieveById($id);

    /**
     * Returns many models by an array of IDs.
     *
     * @param array $ids
     * @return mixed
     */
    abstract protected function retrieveManyById(array $ids);

}
