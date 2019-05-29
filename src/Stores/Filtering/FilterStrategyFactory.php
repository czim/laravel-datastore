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
     * @param bool   $isReversed
     * @return FilterStrategyInterface
     */
    public function make($strategy, $isReversed = false)
    {
        /** @var FilterStrategyInterface $instance */
        $instance = app($this->getClassForStrategy($strategy));

        if ($isReversed) {
            $instance->setReversed();
        }

        $this->clearDriver();

        return $instance;
    }


    /**
     * @param string $strategy
     * @return string
     */
    protected function getClassForStrategy($strategy)
    {
        $strategy = $strategy ?: config('datastore.filter.default');

        return config(
            "datastore.filter.class-map.{$this->getDriverString()}.{$strategy}",
            config("datastore.filter.class-map-default.{$strategy}")
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
