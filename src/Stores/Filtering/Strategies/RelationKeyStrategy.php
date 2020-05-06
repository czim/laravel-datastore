<?php
namespace Czim\DataStore\Stores\Filtering\Strategies;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder;

class RelationKeyStrategy extends AbstractFilterStrategy
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

        return $query->whereHas(
            $relation,
            function ($query) use ($key, $value) {
                /** @var Builder $query */

                if (is_array($value) || $value instanceof Arrayable) {
                    return $query->whereIn($key, $value);
                }

                return $query->where($key, $value);
            },
            $this->getWhereHasConditional(),
            $this->getWhereHasCount()
        );
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

        return $instance->getModel()->getQualifiedKeyName();
    }

    /**
     * @return string
     */
    protected function getWhereHasConditional()
    {
        return $this->isReversed() ? '<' : '>=';
    }

    /**
     * @return int
     */
    protected function getWhereHasCount()
    {
        return 1;
    }

}
