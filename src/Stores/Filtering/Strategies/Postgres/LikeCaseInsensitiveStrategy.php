<?php
namespace Czim\DataStore\Stores\Filtering\Strategies\Postgres;

use Czim\DataStore\Contracts\Stores\Filtering\FilterStrategyInterface;
use DB;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;

class LikeCaseInsensitiveStrategy implements FilterStrategyInterface
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
        return $query->where(DB::raw('lower(' . $column . ')'), 'like', '%' . strtolower($value) . '%');
    }

}
