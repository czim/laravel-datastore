<?php
namespace Czim\DataStore\Contracts\Context;

interface FilterContextInterface
{

    /**
     * Returns filter data to apply.
     *
     * @return array    associative
     */
    public function filters();

    /**
     * Returns filter data to apply by key.
     *
     * @param string $key
     * @return mixed
     */
    public function filter($key);

}
