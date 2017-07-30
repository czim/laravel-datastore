<?php
namespace Czim\DataStore\Stores\Filtering\Strategies;

use Czim\DataStore\Contracts\Stores\Filtering\FilterStrategyInterface;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;

class ExactCommaSeparatedStrategy implements FilterStrategyInterface
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

        return $query->whereIn($column, $value);
    }

}
