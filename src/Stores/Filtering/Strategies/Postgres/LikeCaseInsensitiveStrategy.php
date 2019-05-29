<?php
namespace Czim\DataStore\Stores\Filtering\Strategies\Postgres;

use Czim\DataStore\Stores\Filtering\Strategies\AbstractFilterStrategy;
use DB;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;

class LikeCaseInsensitiveStrategy extends AbstractFilterStrategy
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
        $conditional = $this->isReversed() ? 'not like' : 'like';

        return $query->where(DB::raw('lower(' . $column . ')'), $conditional, '%' . strtolower($value) . '%');
    }

}
