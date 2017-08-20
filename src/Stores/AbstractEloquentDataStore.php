<?php
namespace Czim\DataStore\Stores;

use Czim\DataObject\Contracts\DataObjectInterface;
use Czim\DataStore\Context\SortKey;
use Czim\DataStore\Contracts\Context\ContextInterface;
use Czim\DataStore\Contracts\Resource\ResourceAdapterInterface;
use Czim\DataStore\Contracts\Stores\DataStoreInterface;
use Czim\DataStore\Contracts\Stores\Filtering\FilterStrategyFactoryInterface;
use Czim\DataStore\Contracts\Stores\Manipulation\DataManipulatorInterface;
use Czim\DataStore\Contracts\Stores\Sorting\SortStrategyFactoryInterface;
use Czim\DataStore\Enums\FilterStrategyEnum;
use Czim\DataStore\Enums\SortStrategyEnum;
use Czim\DataStore\Exceptions\FeatureNotSupportedException;
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
     * @var DataManipulatorInterface|null
     */
    protected $manipulator;

    /**
     * @var string
     */
    protected $modelClass;

    /**
     * Database strategy driver key.
     *
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
     * The default page size to use if none specified.
     *
     * @var int|null
     */
    protected $defaultPageSize;


    public function __construct()
    {
        $this->defaultPageSize = config('datastore.pagination.size');
    }


    /**
     * Sets the resource adapter.
     *
     * @param ResourceAdapterInterface $resourceAdapter
     * @return $this
     */
    public function setResourceAdapter(ResourceAdapterInterface $resourceAdapter)
    {
        $this->resourceAdapter = $resourceAdapter;

        return $this;
    }

    /**
     * Sets the database strategy driver key.
     *
     * @param string $driver
     * @return $this
     */
    public function setStrategyDriver($driver)
    {
        $this->strategyDriver = $driver;

        return $this;
    }

    /**
     * Sets the manipulator to use, if any.
     *
     * If no manipulator is set, record manipulation is not supported.
     *
     * @param DataManipulatorInterface|null $manipulator
     * @return $this
     */
    public function setManipulator(DataManipulatorInterface $manipulator = null)
    {
        $this->manipulator = $manipulator;

        return $this;
    }

    /**
     * Sets the default page size to use if none specified.
     *
     * @param int $size
     * @return $this
     */
    public function setDefaultPageSize($size)
    {
        $this->defaultPageSize = (int) $size;

        return $this;
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

            $clonedQuery = clone $query;

            $total = $clonedQuery->count();
            $page  = max($context->pageNumber(), 1);
            $size  = $context->pageSize() ?: $this->defaultPageSize;

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
     * @param Builder   $query
     * @param SortKey[] $sorting
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
                if ($attribute instanceof SortKey) {
                    return in_array($attribute->getKey(), $available);
                }
                // @codeCoverageIgnoreStart
                return in_array($attribute, $available);
                // @codeCoverageIgnoreEnd
            });
        }

        // @codeCoverageIgnoreStart
        if ([] === $sorting) {
            return $query;
        }
        // @codeCoverageIgnoreEnd

        foreach ($sorting as $sort) {

            // @codeCoverageIgnoreStart
            if ( ! ($sort instanceof $sort)) {
                $sort = new SortKey($sort);
            }
            // @codeCoverageIgnoreEnd

            $attribute = $this->resourceAdapter->dataKeyForAttribute($sort->getKey());

            $this->applySortParameter($query, $attribute, $sort->isReversed());
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
    //      Manipulation
    // ------------------------------------------------------------------------------

    /**
     * Creates a new record with given JSON-API data.
     *
     * @param DataObjectInterface $data
     * @return false|mixed
     * @throws FeatureNotSupportedException
     */
    public function create(DataObjectInterface $data)
    {
        if (null === $this->manipulator) {
            throw new FeatureNotSupportedException('No manipulator set');
        }

        return $this->manipulator->create($data);
    }

    /**
     * Updates a record by ID with given JSON-API data.
     *
     * @param mixed               $id
     * @param DataObjectInterface $data
     * @return bool
     * @throws FeatureNotSupportedException
     */
    public function updateById($id, DataObjectInterface $data)
    {
        if (null === $this->manipulator) {
            throw new FeatureNotSupportedException('No manipulator set');
        }

        return $this->manipulator->updateById($id, $data);
    }

    /**
     * Deletes a record by ID.
     *
     * @param mixed $id
     * @return bool
     * @throws FeatureNotSupportedException
     */
    public function deleteById($id)
    {
        if (null === $this->manipulator) {
            throw new FeatureNotSupportedException('No manipulator set');
        }

        return $this->manipulator->deleteById($id);
    }

    /**
     * Attaches records as related to a given record.
     *
     * @param mixed  $id
     * @param string $include
     * @param array  $ids
     * @param bool   $detaching
     * @return bool
     * @throws FeatureNotSupportedException
     */
    public function attachAsRelated($id, $include, array $ids, $detaching = false)
    {
        if (null === $this->manipulator) {
            throw new FeatureNotSupportedException('No manipulator set');
        }

        $relation = $this->resourceAdapter->dataKeyForInclude($include);

        return $this->manipulator->attachAsRelated($id, $relation, $ids, $detaching);
    }

    /**
     * Detaches records as related to a given record.
     *
     * @param mixed  $id
     * @param string $include
     * @param array  $ids
     * @return bool
     * @throws FeatureNotSupportedException
     */
    public function detachAsRelated($id, $include, array $ids)
    {
        if (null === $this->manipulator) {
            throw new FeatureNotSupportedException('No manipulator set');
        }

        $relation = $this->resourceAdapter->dataKeyForInclude($include);

        return $this->manipulator->detachAsRelated($id, $relation, $ids);
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
