<?php
namespace Czim\DataStore\Stores\Filtering\Strategies\Postgres;

use Czim\DataStore\Contracts\Stores\Filtering\FilterStrategyInterface;
use DB;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;

class ExactCaseInsensitiveStrategy implements FilterStrategyInterface
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
            return $query->whereIn(DB::raw('lower(' . $column . ')'), array_map('strtolower', $value));
        }

        return $query->where(DB::raw('lower(' . $column . ')'), '=', strtolower($value));
    }

}
