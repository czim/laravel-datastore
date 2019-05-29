<?php
namespace Czim\DataStore\Stores\Filtering\Strategies\Postgres;

use Czim\DataStore\Stores\Filtering\Strategies\AbstractFilterStrategy;
use DB;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;

class ExactCaseInsensitiveStrategy extends AbstractFilterStrategy
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

            if ($this->isReversed()) {
                return $query->whereNotIn(DB::raw('lower(' . $column . ')'), array_map('strtolower', $value));
            }

            return $query->whereIn(DB::raw('lower(' . $column . ')'), array_map('strtolower', $value));
        }

        $conditional = $this->isReversed() ? '!=' : '=';

        return $query->where(DB::raw('lower(' . $column . ')'), $conditional, strtolower($value));
    }

}
