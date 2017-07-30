<?php
namespace Czim\DataStore\Stores;

use App\Enums\FilterStrategyEnum;
use App\Enums\SortStrategyEnum;
use Czim\DataObject\Contracts\DataObjectInterface;
use Czim\DataStore\Contracts\Context\ContextInterface;
use Czim\DataStore\Contracts\Stores\DataStoreInterface;
use Czim\DataStore\Contracts\Resource\ResourceAdapterInterface;
use Czim\DataStore\Strategies\Filter\EloquentFilter;
use Czim\DataStore\Strategies\Sorting\EloquentSorter;
use Czim\Repository\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentDataStore implements DataStoreInterface
{

    /**
     * @var BaseRepositoryInterface
     */
    protected $repository;

    /**
     * @var ResourceAdapterInterface
     */
    protected $resourceAdapter;

    /**
     * Strategies to use for JSON-API sort names (without '-' prefix).
     *
     * @var string[]
     */
    protected $sortStrategies = [];


    /**
     * @param BaseRepositoryInterface  $repository
     * @param ResourceAdapterInterface $resourceAdapter
     */
    public function __construct(BaseRepositoryInterface $repository, ResourceAdapterInterface $resourceAdapter)
    {
        $this->repository      = $repository;
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
        $this->applyIncludes($includes);

        return $this->repository->find($id);
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
        $this->applyIncludes($includes);

        return $this->repository->query()->whereIn('id', $ids);
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
        $this->applyIncludes($includes);

        $query = $this->repository->query();

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

        // Filter by IDs
        if (array_has($filters, 'ids')) {

            $ids = array_get($filters, 'ids');

            if ( ! is_array($ids)) {
                $ids = array_map('trim', explode(',', $ids));
            }

            $query->whereIn('id', $ids);
        }

        // Special filters? Only consider available in resource
        $available = $this->resourceAdapter->availableFilterKeys();

        $filters = array_filter($filters, function ($key) use ($available) {
            return in_array($key, $available);
        }, ARRAY_FILTER_USE_KEY);

        foreach ($filters as $key => $value) {
            $this->applyFilterValue($query, $key, $value);
        }

        return $query;
    }

    /**
     * Applies a single filter value to a query.
     *
     * @param Builder $query
     * @param string  $key
     * @param mixed   $value
     * @return Builder
     */
    protected function applyFilterValue($query, $key, $value)
    {
        $attribute = $this->resourceAdapter->dataKeyForAttribute($key);
        $strategy  = $this->determineFilterStrategyForKey($attribute);

        $filter = new EloquentFilter($query);

        return $filter->apply($strategy, $attribute, $value);
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
            'datastore.filter.strategies.' . $this->repository->model(),
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

        $sorter = new EloquentSorter($query);

        return $sorter->apply($strategy, $attribute, $reverse);
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
            'datastore.sort.strategies.' . $this->repository->model(),
            config(
                "datastore.sort.default-strategies.{$attribute}",
                config('datastore.sort.default', SortStrategyEnum::ALPHABETIC)
            )
        );
    }

    /**
     * @param array $includes
     * @return $this
     */
    protected function applyIncludes(array $includes)
    {
        // todo: consider sensible recursive logic with per-resource nested handling
        return $this;
    }


    // ------------------------------------------------------------------------------
    //      Updating
    // ------------------------------------------------------------------------------

    /**
     * Creates a new record with given JSON-API data.
     *
     * @param DataObjectInterface $data
     * @return mixed|false
     */
    public function create(DataObjectInterface $data)
    {
        // TODO: Implement create() method.
    }

    /**
     * Updates a record by ID with given JSON-API data.
     *
     * @param string              $id
     * @param DataObjectInterface $data
     * @return bool
     */
    public function updatedById($id, DataObjectInterface $data)
    {
        // TODO: Implement updatedById() method.
    }

    /**
     * Deletes a record by ID.
     *
     * @param string $id
     * @return bool
     */
    public function deleteById($id)
    {
        // TODO: Implement deleteById() method.
    }

}
