<?php
namespace Czim\DataStore\Contracts\Stores\Sorting;

interface SortStrategyFactoryInterface
{

    /**
     * Sets the driver for next build.
     *
     * @param string $string
     * @return $this
     */
    public function driver($string);

    /**
     * Makes a sort strategy instance.
     *
     * @param string $strategy
     * @return SortStrategyInterface
     */
    public function make($strategy);

}
