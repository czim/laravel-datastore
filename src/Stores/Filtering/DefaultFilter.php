<?php
namespace Czim\DataStore\Stores\Filtering;

use Czim\DataStore\Contracts\Resource\ResourceAdapterInterface;
use Czim\DataStore\Contracts\Stores\Filtering\FilterHandlerInterface;
use Czim\DataStore\Contracts\Stores\Filtering\FilterStrategyFactoryInterface;
use Czim\DataStore\Enums\FilterStrategyEnum;
use Czim\DataStore\Stores\Filtering\Data\DefaultFilterData;
use Czim\Filter\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class DefaultFilter extends Filter implements FilterHandlerInterface
{

    /**
     * @var Model|null
     */
    protected $model;

    /**
     * @var ResourceAdapterInterface|null
     */
    protected $resourceAdapter;

    /**
     * @var FilterStrategyFactoryInterface
     */
    protected $strategyFactory;

    /**
     * The classname for the FilterData that should be constructed
     *
     * @var string
     */
    protected $filterDataClass = DefaultFilterData::class;


    public function __construct()
    {
        parent::__construct([]);
    }


    /**
     * Sets the parent model instance.
     *
     * @param Model $model
     * @return $this
     */
    public function setModel(Model $model)
    {
        $this->model = $model;

        return $this;
    }

    /**
     * Sets the resource adapter for the main model.
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
     * Sets the stratey factory interface.
     *
     * @param FilterStrategyFactoryInterface $factory
     * @return $this
     */
    public function setStrategyFactory(FilterStrategyFactoryInterface $factory)
    {
        $this->strategyFactory = $factory;

        return $this;
    }

    /**
     * Sets the raw data to filter by.
     *
     * @param array $data
     * @param array $available  available keys
     * @return $this
     */
    public function setData(array $data, array $available = [])
    {
        if ( ! count($available) && $this->resourceAdapter) {
            $available = $this->resourceAdapter->availableFilterKeys();
        }

        $defaults = $this->buildDataDefaultsForKeys($available);

        $this->data = new $this->filterDataClass($data, $defaults);

        return $this;
    }

    /**
     * @param array $keys
     * @return array
     */
    protected function buildDataDefaultsForKeys(array $keys)
    {
        $defaults = [];

        foreach ($keys as $key) {
            $defaults[ $key ] = null;
        }

        return $defaults;
    }

    /**
     * {@inheritdoc}
     */
    protected function applyParameter($parameterName, $parameterValue, $query)
    {
        // The parameter may be either a resource attribute or resource include.
        // If the parameter is custom and matches neither type, a custom strategy
        // must be used to handle it correctly.
        if (in_array($parameterName, $this->resourceAdapter->availableIncludeKeys())) {
            $key        = $this->resourceAdapter->dataKeyForInclude($parameterName);
            $isRelation = true;
        } else {
            $key        = $this->resourceAdapter->dataKeyForAttribute($parameterName);
            $isRelation = false;
        }

        // If the parameter is for a relation, there may be nested keys
        // to be filtered on the related items, this is not currently supported.
        // ie.: comments.name = '%test%' to filter a post with comments with specific names.
        // todo?

        $this->applyFilterValue(
            $query,
            $this->determineStrategyForKey($parameterName),
            $key,
            $parameterValue
        );


        parent::applyParameter($parameterName, $parameterValue, $query);
    }


    /**
     * Returns the filter strategy alias to use for a given filter key.
     *
     * @param string $key
     * @return string
     */
    protected function determineStrategyForKey($key)
    {
        $modelClass = get_class($this->model);

        return config(
            "datastore.filter.strategies.{$modelClass}",
            config(
                "datastore.filter.default-strategies.{$key}",
                config('datastore.filter.default', FilterStrategyEnum::LIKE)
            )
        );
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
        return $this->strategyFactory->make($strategy)
            ->apply($query, $key, $value);
    }

}
