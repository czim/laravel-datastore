<?php
namespace Czim\DataStore\Contracts\Stores\Filtering;

use Czim\DataStore\Contracts\Resource\ResourceAdapterInterface;
use Czim\Filter\Contracts\FilterInterface;
use Illuminate\Database\Eloquent\Model;

interface FilterHandlerInterface extends FilterInterface
{

    /**
     * Sets the parent model instance.
     *
     * @param Model $model
     * @return $this
     */
    public function setModel(Model $model);

    /**
     * Sets the resource adapter for the main model.
     *
     * @param ResourceAdapterInterface $resourceAdapter
     * @return $this
     */
    public function setResourceAdapter(ResourceAdapterInterface $resourceAdapter);

    /**
     * Sets the stratey factory interface.
     *
     * @param FilterStrategyFactoryInterface $factory
     * @return $this
     */
    public function setStrategyFactory(FilterStrategyFactoryInterface $factory);

    /**
     * Sets the raw data to filter by.
     *
     * @param array $data
     * @param array $available  available keys
     * @return $this
     */
    public function setData(array $data, array $available = []);

}
