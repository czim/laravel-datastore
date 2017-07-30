<?php
namespace Czim\DataStore\Stores\Filtering\Strategies;

use Czim\DataStore\Contracts\Stores\Filtering\FilterStrategyInterface;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;

class ExactStrategy implements FilterStrategyInterface
{

    /**
     * Applies filter to query.
     *
     * @param Builder|EloquentBuilder $query
     * @param string $column
     * @param mixed $value
     * @return Builder|EloquentBuilder
     */
    public function apply($query, $column, $value)
    {
        if (is_array($value)) {
            return $query->whereIn($column, $value);
        }

        return $query->where($column, '=', $value);
    }

}
