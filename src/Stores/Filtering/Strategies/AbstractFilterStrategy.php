<?php

namespace Czim\DataStore\Stores\Filtering\Strategies;

use Czim\DataStore\Contracts\Stores\Filtering\FilterStrategyInterface;

abstract class AbstractFilterStrategy implements FilterStrategyInterface
{

    /**
     * @var bool
     */
    protected $reversed = false;


    /**
     * Marks the filter strategy as being reversed (exclusive).
     */
    public function setReversed()
    {
        $this->reversed = true;
    }


    /**
     * @return bool
     */
    protected function isReversed()
    {
        return $this->reversed;
    }

}
