<?php
namespace Czim\DataStore\Stores\Filtering\Strategies;

use Czim\DataStore\Contracts\Stores\Filtering\FilterStrategyInterface;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder;

class RelationKeyStrategy implements FilterStrategyInterface
{

    /**
     * Applies filter to query.
     *
     * @param Builder|EloquentBuilder $query
     * @param string $relation
     * @param mixed $value
     * @return Builder|EloquentBuilder
     */
    public function apply($query, $relation, $value)
    {
        $key = $this->getModelKeyForRelation($query, $relation);

        return $query->whereHas($relation, function ($query) use ($key, $value) {
            /** @var Builder $query */

            if (is_array($value) || $value instanceof Arrayable) {
                return $query->whereIn($key, $value);
            }

            return $query->where($key, $value);
        });
    }

    /**
     * @param Builder|EloquentBuilder $query
     * @param string                  $relation
     * @return string
     */
    protected function getModelKeyForRelation($query, $relation)
    {
        /** @var Relation $instance */
        $instance = $query->getModel()->{$relation}();

        return $instance->getModel()->getKeyName();
    }

}
