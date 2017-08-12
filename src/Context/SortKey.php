<?php
namespace Czim\DataStore\Context;

class SortKey
{

    /**
     * @var string
     */
    protected $key;

    /**
     * @var bool
     */
    protected $reversed = false;


    /**
     * @param string $key
     * @param bool   $reversed
     */
    public function __construct($key, $reversed = false)
    {
        $this->key      = $key;
        $this->reversed = (bool) $reversed;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Returns the sort direction
     *
     * @return string   asc or desc
     */
    public function getDirection()
    {
        return $this->isReversed() ? 'desc' : 'asc';
    }

    /**
     * Returns whether the sort direction is reversed (descending).
     *
     * @return bool
     */
    public function isReversed()
    {
        return $this->reversed;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return ($this->reversed ? '-' : null) . $this->key;
    }

}
