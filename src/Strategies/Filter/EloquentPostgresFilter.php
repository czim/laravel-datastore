<?php
namespace Czim\DataStore\Strategies\Filter;

use DB;

class EloquentPostgresFilter extends EloquentFilter
{

    protected function applyExactCaseInsensitiveFilter()
    {
        if (is_array($this->value)) {
            $this->query->whereIn($this->key, $this->value);
        } else {
            $this->query->where(DB::raw('lower(' . $this->key . ')'), '=', strtolower($this->value));
        }
    }

    protected function applyLikeCaseInsensitiveFilter()
    {
        $this->query->where(DB::raw('lower(' . $this->key . ')'), 'like', '%' . strtolower($this->value) . '%');
    }

}
