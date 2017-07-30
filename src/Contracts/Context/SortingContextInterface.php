<?php
namespace Czim\DataStore\Contracts\Context;

interface SortingContextInterface
{

    /**
     * Returns sorting keys to apply in specific order.
     *
     * @return string[]
     */
    public function sorting();

}
