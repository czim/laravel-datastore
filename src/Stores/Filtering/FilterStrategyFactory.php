<?php
namespace Czim\DataStore\Stores\Filtering;

use Czim\DataStore\Contracts\Stores\Filtering\FilterStrategyFactoryInterface;
use Czim\DataStore\Contracts\Stores\Filtering\FilterStrategyInterface;

class FilterStrategyFactory implements FilterStrategyFactoryInterface
{
    /**
     * @var string|null
     */
    protected $driver;

    /**
     * Sets the driver for next build.
     *
     * @param string $driver
     * @return $this
     */
    public function driver($driver)
    {
        $this->driver = $driver;

        return $this;
    }

    /**
     * Makes a filter strategy instance.
     *
     * @param string $strategy
     * @return FilterStrategyInterface
     */
    public function make($strategy)
    {
        return app($this->getClassForStrategy($strategy));
    }


    protected function getClassForStrategy($strategy)
    {
        return config(
            'datastore.drivers.strategies.filtering.' . $this->getDriverString() . '.map.' . $strategy,
            'datastore.drivers.strategies.filtering.' . $this->getDriverString() . '.default'
        );
    }

    /**
     * @return string
     */
    protected function getDriverString()
    {
        return $this->driver ?: config('datastore.drivers.strategies.default', 'mysql');
    }

    /**
     * @return $this
     */
    protected function clearDriver()
    {
        $this->driver = null;

        return $this;
    }
    
}
