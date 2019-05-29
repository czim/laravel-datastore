<?php
namespace Czim\DataStore\Stores\Filtering\Strategies;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;

class ExactCommaSeparatedStrategy extends AbstractFilterStrategy
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
        if ($value instanceof Arrayable) {
            $value = $value->toArray();
        }

        if ( ! is_array($value)) {
            $value = array_map('trim', explode(',', $value));
        }

        if ($this->isReversed()) {
            return $query->whereNotIn($column, $value);
        }

        return $query->whereIn($column, $value);
    }

}
