<?php
namespace Czim\DataStore\Contracts\Stores\Sorting;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;

interface SortStrategyInterface
{

    /**
     * Applies filter to query.
     *
     * @param Builder|EloquentBuilder $query
     * @param string                  $column
     * @param bool                    $reverse
     * @return Builder|EloquentBuilder
     */
    public function apply($query, $column, $reverse);

}
