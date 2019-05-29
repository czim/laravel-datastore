<?php
namespace Czim\DataStore\Stores\Filtering\Strategies;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;

class ExactStrategy extends AbstractFilterStrategy
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
                return $query->whereNotIn($column, $value);
            }

            return $query->whereIn($column, $value);
        }

        $conditional = $this->isReversed() ? '!=' : '=';

        return $query->where($column, $conditional, $value);
    }

}
