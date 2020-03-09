<?php
namespace Czim\DataStore\Stores;

use Czim\DataObject\Contracts\DataObjectInterface;
use Czim\DataStore\Context\SortKey;
use Czim\DataStore\Contracts\Context\ContextInterface;
use Czim\DataStore\Contracts\Resource\ResourceAdapterInterface;
use Czim\DataStore\Contracts\Stores\DataStoreInterface;
use Czim\DataStore\Contracts\Stores\Filtering\FilterHandlerInterface;
use Czim\DataStore\Contracts\Stores\Includes\IncludeDecoratorInterface;
use Czim\DataStore\Contracts\Stores\Includes\IncludeResolverInterface;
use Czim\DataStore\Contracts\Stores\Manipulation\DataManipulatorInterface;
use Czim\DataStore\Contracts\Stores\Sorting\SortStrategyFactoryInterface;
use Czim\DataStore\Enums\SortStrategyEnum;
use Czim\DataStore\Exceptions\FeatureNotSupportedException;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

abstract class AbstractEloquentDataStore implements DataStoreInterface
{

    /**
     * @var ResourceAdapterInterface
     */
    protected $resourceAdapter;

    /**
     * @var IncludeResolverInterface
     */
    protected $includeResolver;

    /**
     * This decorator is optional and may be used to turn string includes into closures,
     * add default includes, etc.
     *
     * @var IncludeDecoratorInterface|null
     */
    protected $includeDecorator;

    /**
     * @var FilterHandlerInterface|null
     */
    protected $filter;

    /**
     * @var DataManipulatorInterface|null
     */
    protected $manipulator;

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
     * Whether includes are queued for a collection query (as opposed to a single).
     *
     * @var bool
     */
    protected $includeForMany = false;

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
     * Sets the include resolver.
     *
     * @param IncludeResolverInterface $resolver
     * @return $this
     */
    public function setIncludeResolver(IncludeResolverInterface $resolver)
    {
        $this->includeResolver = $resolver;

        return $this;
    }

    /**
     * Sets the include decorator instance.
     *
     * @param IncludeDecoratorInterface $decorator
     * @return $this
     */
    public function setIncludeDecorator(IncludeDecoratorInterface $decorator)
    {
        $this->includeDecorator = $decorator;

        return $this;
    }

    /**
     * Sets the filter handlers.
     *
     * @param FilterHandlerInterface $filter
     * @return $this
     */
    public function setFilterHandler(FilterHandlerInterface $filter)
    {
        $this->filter = $filter;

        return $this;
    }

    /**
     * Returns the filter handler.
     *
     * @return FilterHandlerInterface|null
     */
    public function getFilterHandler()
    {
        return $this->filter;
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
        $this->queueIncludes($includes, false);

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
        $this->queueIncludes($includes, true);

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
        $this->queueIncludes($includes, true);

        $query = $this->retrieveQuery();

        $query = $this->applyFilters($query, $context->filters());
        $query = $this->applySorting($query, $context->sorting());

        if ($context->shouldBePaginated()) {

            $clonedQuery = clone $query;

            $total = $clonedQuery->count();
            $page  = max($context->pageNumber(), 1);
            $size  = $context->pageSize() ?: $this->defaultPageSize;

            return new LengthAwarePaginator(
                $query->take($size)->skip(($page - 1) * $size)->get(),
                $total,
                $size,
                $page
            );
        }

        return $query->get();
    }


    // ------------------------------------------------------------------------------
    //      Filtering & Sorting
    // ------------------------------------------------------------------------------

    /**
     * Applies filters to a query.
     *
     * @param Builder|EloquentBuilder $query
     * @param array                   $filters
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
            return $this->isPartOfAvailableFilters($key, $available);
        }, ARRAY_FILTER_USE_KEY);


        if ( ! count($filters)) {
            return $query;
        }

        if ( ! $this->filter) {
            throw new RuntimeException("No filter handler set, filters cannot be applied");
        }

        return $this->filter
            ->setData($filters, $available)
            ->apply($query);
    }

    /**
     * Returns whether the given key is (prefixed or not) part of the available filters.
     *
     * @param string   $key
     * @param string[] $available
     * @return bool
     */
    protected function isPartOfAvailableFilters($key, array $available)
    {
        return in_array($this->stripReversalPrefix($key), $available);
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
            "datastore.sort.strategies.{$this->getModelClass()}",
            config(
                "datastore.sort.default-strategies.{$attribute}",
                config('datastore.sort.default', SortStrategyEnum::ALPHABETIC)
            )
        );
    }


    // ------------------------------------------------------------------------------
    //      Strategies
    // ------------------------------------------------------------------------------

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
    //      Includes and Nested data
    // ------------------------------------------------------------------------------

    /**
     * Prepares datastore to eager load the given includes.
     *
     * @param array $includes
     * @param bool  $many
     * @return $this
     */
    protected function queueIncludes(array $includes, $many = false)
    {
        $this->includeForMany = (bool) $many;
        $this->includes       = $includes;

        return $this;
    }

    /**
     * Takes a list of resource includes and resolves them to eager-loadable include array.
     *
     * The provided includes are expected to be resource-based,
     * so they must be adjusted to be data-based here.
     *
     * @param array $includes
     * @return array
     */
    protected function resolveIncludesForEagerLoading(array $includes)
    {
        if (empty($includes)) {
            $includes = [];
        } else {
            $includes = $this->includeResolver->resolve($includes);
        }

        if ( ! $this->includeDecorator) {
            return $includes;
        }

        return $this->includeDecorator->decorate($includes, $this->includeForMany);
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
        $this->verifyManipulatorIsSet();

        $data = $this->convertResourceAttributesToDataKeys($data);

        return $this->manipulator->create($data);
    }

    /**
     * Makes a record without persisting it.
     *
     * @param DataObjectInterface $data
     * @return false|mixed
     * @throws FeatureNotSupportedException
     */
    public function make(DataObjectInterface $data)
    {
        $this->verifyManipulatorIsSet();

        $data = $this->convertResourceAttributesToDataKeys($data);

        return $this->manipulator->make($data);
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
        $this->verifyManipulatorIsSet();

        $data = $this->convertResourceAttributesToDataKeys($data);

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
        $this->verifyManipulatorIsSet();

        return $this->manipulator->deleteById($id);
    }

    /**
     * Attaches or replaces records for a relationship.
     *
     * @param mixed|Model              $parent
     * @param string                   $include
     * @param mixed|Collection|Model[] $records
     * @param bool                     $detaching   if true, everything but the given records are detached
     * @return bool
     */
    public function attachRelatedRecords($parent, $include, $records, $detaching = false)
    {
        $this->verifyManipulatorIsSet();

        $relationMethod = $this->resourceAdapter->dataKeyForInclude($include);

        if ( ! $relationMethod) {
            throw new InvalidArgumentException("Relation method could not be resolved for include '{$include}'");
        }

        return $this->manipulator->attachRelatedRecords($parent, $relationMethod, $records, $detaching);
    }

    /**
     * Detaches records for a relationship.
     *
     * @param mixed|Model        $parent
     * @param string             $include
     * @param Collection|Model[] $records
     * @return bool
     * @throws FeatureNotSupportedException
     */
    public function detachRelatedRecords($parent, $include, $records)
    {
        $this->verifyManipulatorIsSet();

        $relationMethod = $this->resourceAdapter->dataKeyForInclude($include);

        if ( ! $relationMethod) {
            throw new InvalidArgumentException("Relation method could not be resolved for include '{$include}'");
        }

        return $this->manipulator->detachRelatedRecords($parent, $relationMethod, $records);
    }

    /**
     * Detaches records by ID for a relationship.
     *
     * @param mixed|Model        $parent
     * @param string             $include
     * @param Collection|mixed[] $ids
     * @return bool
     * @throws FeatureNotSupportedException
     */
    public function detachRelatedRecordsById($parent, $include, $ids)
    {
        $this->verifyManipulatorIsSet();

        $relationMethod = $this->resourceAdapter->dataKeyForInclude($include);

        if ( ! $relationMethod) {
            throw new InvalidArgumentException("Relation method could not be resolved for include '{$include}'");
        }

        return $this->manipulator->detachRelatedRecordsById($parent, $relationMethod, $ids);
    }

    /**
     * Verifies that a manipulator instance if set, throws an exception otherwise.
     *
     * @throws FeatureNotSupportedException
     */
    protected function verifyManipulatorIsSet()
    {
        if (null === $this->manipulator) {
            throw new FeatureNotSupportedException('No manipulator set');
        }
    }

    /**
     * Converts keys from resource to data for a given data object.
     *
     * @param DataObjectInterface $data
     * @return DataObjectInterface
     */
    protected function convertResourceAttributesToDataKeys(DataObjectInterface $data)
    {
        $resolvedData  = clone $data;
        $resolvedData->clear();

        foreach ($data->getKeys() as $key) {
            $resolvedData[ $this->resourceAdapter->dataKeyForAttribute($key) ] = $data->getAttribute($key);
        }

        return $resolvedData;
    }


    /**
     * Returns the key without the reversal prefix, if it has any.
     *
     * @param string $key
     * @return bool|string
     */
    protected function stripReversalPrefix($key)
    {
        if ($this->reversalPrefix() !== null && ! Str::startsWith($key, $this->reversalPrefix())) {
            return $key;
        }

        return substr($key, strlen($this->reversalPrefix()));
    }

    /**
     * @return null|string
     */
    protected function reversalPrefix()
    {
        return config('datastore.filter.reverse-key-prefix', null) ?: null;
    }


    /**
     * @return string|null
     */
    protected function getModelClass()
    {
        return get_class($this->getModel());
    }

    // ------------------------------------------------------------------------------
    //      Abstract
    // ------------------------------------------------------------------------------

    /**
     * Returns a model instance.
     *
     * @return Model
     */
    abstract public function getModel();

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
