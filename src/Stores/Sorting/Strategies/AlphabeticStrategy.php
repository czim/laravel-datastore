<?php
namespace Czim\DataStore\Stores\Sorting\Strategies;

use Czim\DataStore\Contracts\Stores\Sorting\SortStrategyInterface;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;

class AlphabeticStrategy implements SortStrategyInterface
{

    /**
     * Applies sorting to query.
     *
     * @param Builder|EloquentBuilder $query
     * @param string                  $column
     * @param bool                    $reverse
     * @return Builder|EloquentBuilder
     */
    public function apply($query, $column, $reverse)
    {
        return $query->orderBy($column, $reverse ? 'desc' : 'asc');
    }

}
