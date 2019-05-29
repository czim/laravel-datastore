<?php
namespace Czim\DataStore\Stores\Filtering;

use Czim\DataStore\Contracts\Resource\ResourceAdapterInterface;
use Czim\DataStore\Contracts\Stores\Filtering\FilterHandlerInterface;
use Czim\DataStore\Contracts\Stores\Filtering\FilterStrategyFactoryInterface;
use Czim\DataStore\Enums\FilterStrategyEnum;
use Czim\DataStore\Stores\Filtering\Data\DefaultFilterData;
use Czim\Filter\Filter;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
use RuntimeException;
use UnexpectedValueException;

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
        if ( ! count($available)) {
            if ($this->resourceAdapter) {
                $available = $this->resourceAdapter->availableFilterKeys();
            } else {
                $available = array_keys($data);
            }
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

        $reversePrefix = $this->reversalPrefix();

        foreach ($keys as $key) {
            $defaults[ $key ] = null;

            // Add the reverse-prefixed versions of all keys to the defaults aswell
            if ($reversePrefix !== null) {
                $defaults[ $reversePrefix . $key ] = null;
            }
        }

        return $defaults;
    }

    /**
     * {@inheritdoc}
     */
    protected function applyParameter($parameterName, $parameterValue, $query)
    {
        $baseParameterName = $this->stripReversalPrefix($parameterName);

        // The parameter may be either a resource attribute or resource include.
        // If the parameter is custom and matches neither type, a custom strategy
        // must be used to handle it correctly.
        $key = $this->resolveDataKeyForParameterName($baseParameterName);

        // If the parameter is for a relation, there may be nested keys
        // to be filtered on the related items, this is not currently supported.
        // ie.: comments.name = '%test%' to filter a post with comments with specific names.
        // todo?

        if ($key) {
            $this->applyFilterValue(
                $query,
                $this->determineStrategyForKey($baseParameterName),
                $key,
                $parameterValue,
                $this->isFilterReversed($parameterName)
            );
            return;
        }

        parent::applyParameter($parameterName, $parameterValue, $query);
    }

    /**
     * @param string $name
     * @return string
     */
    protected function resolveDataKeyForParameterName($name)
    {
        if ( ! $this->resourceAdapter) {
            return $name;
        }

        if (in_array($name, $this->resourceAdapter->availableIncludeKeys())) {
            return $this->resourceAdapter->dataKeyForInclude($name);
        }

        return $this->resourceAdapter->dataKeyForAttribute($name);
    }

    /**
     * Returns whether a given parameter name matches for an include relation, rather than an attribute.
     * @param string $name
     * @return bool
     */
    protected function isParameterNameForInclude($name)
    {
        if ( ! $this->resourceAdapter) {
            return false;
        }

        return in_array($name, $this->resourceAdapter->availableIncludeKeys());
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
            "datastore.filter.strategies.{$modelClass}.{$key}",
            config("datastore.filter.default-strategies.{$key}", $this->determineStrategyDefault($key))
        );
    }

    /**
     * @param string $key
     * @return bool
     */
    protected function isFilterReversed($key)
    {
        return $this->reversalPrefix() !== null && Str::startsWith($key, $this->reversalPrefix());
    }

    /**
     * @param string $key
     * @return string
     */
    protected function determineStrategyDefault($key)
    {
        if ( ! $this->isParameterNameForInclude($key)) {
            return config('datastore.filter.default', FilterStrategyEnum::LIKE);
        }

        $relationMethod = $this->resourceAdapter->dataKeyForInclude($key);

        return $this->determineStrategyForRelation($relationMethod);
    }

    /**
     * @param string $method
     * @return string
     */
    protected function determineStrategyForRelation($method)
    {
        $relation = $this->getRelationInstanceForMethod($method);

        $strategy = config('datastore.filter.default-relation-strategies.' . get_class($relation));

        if ($strategy) {
            return $strategy;
        }

        return config('datastore.filter.default', FilterStrategyEnum::LIKE);
    }

    /**
     * @param string $method
     * @return Relation
     */
    protected function getRelationInstanceForMethod($method)
    {
        try {
            $relation = $this->model->{$method}();

        } catch (Exception $e) {

            throw new UnexpectedValueException(
                "Failed trying to get relation instance from " . get_class($this->model) . "::{$method}",
                $e->getCode(),
                $e
            );
        }

        if ( ! ($relation instanceof Relation)) {
            throw new UnexpectedValueException(
                "Method " . get_class($this->model) . "::{$method} did not return relation instance"
            );
        }

        return $relation;
    }

    /**
     * Applies a single filter value to a query.
     *
     * @param Builder $query
     * @param string  $strategy
     * @param string  $key
     * @param mixed   $value
     * @param bool    $isReversed
     * @return Builder
     */
    protected function applyFilterValue($query, $strategy, $key, $value, $isReversed = false)
    {
        if ( ! $this->strategyFactory) {
            throw new RuntimeException(
                "Attempting to apply strategy '{$strategy}' without strategy factory instance set"
            );
        }

        return $this->strategyFactory->make($strategy, $isReversed)
            ->apply($query, $key, $value);
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

}
