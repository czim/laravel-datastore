<?php
namespace Czim\DataStore\Strategies\Sorting;

use DB;

class EloquentPostgresSorter extends EloquentSorter
{

    protected function applyAlphabeticSort()
    {
        $this->query->orderBy(DB::raw('lower(' . $this->attribute . ')'), $this->direction());
    }

}
