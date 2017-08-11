<?php
namespace Czim\DataStore\Stores\Sorting;

use Czim\DataStore\Contracts\Stores\Sorting\SortStrategyFactoryInterface;
use Czim\DataStore\Contracts\Stores\Sorting\SortStrategyInterface;

class SortStrategyFactory implements SortStrategyFactoryInterface
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
     * Makes a sort strategy instance.
     *
     * @param string $strategy
     * @return SortStrategyInterface
     */
    public function make($strategy)
    {
        $instance = app($this->getClassForStrategy($strategy));

        $this->clearDriver();

        return $instance;
    }


    /**
     * @param string $strategy
     * @return string
     */
    protected function getClassForStrategy($strategy)
    {
        $strategy = $strategy ?: config('datastore.sort.default');

        return config(
            "datastore.sort.class-map.{$this->getDriverString()}.{$strategy}",
            config("datastore.sort.class-map-default.{$strategy}")
        );
    }

    /**
     * @return string
     */
    protected function getDriverString()
    {
        return $this->driver ?: config('datastore.drivers.database.default', 'mysql');
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
