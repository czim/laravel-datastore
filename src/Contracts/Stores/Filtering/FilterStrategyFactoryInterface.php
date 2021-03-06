<?php
namespace Czim\DataStore\Contracts\Stores\Filtering;

interface FilterStrategyFactoryInterface
{

    /**
     * Sets the driver for next build.
     *
     * @param string $driver
     * @return $this
     */
    public function driver($driver);

    /**
     * Makes a filter strategy instance.
     *
     * @param string $strategy
     * @param bool   $isReversed
     * @return FilterStrategyInterface
     */
    public function make($strategy, $isReversed = false);

}
