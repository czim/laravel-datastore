<?php
namespace Czim\DataStore\Contracts\Stores\Includes;

use Illuminate\Database\Eloquent\Model;

interface IncludeDecoratorInterface
{

    /**
     * Sets the parent model to which the top-level includes are related.
     *
     * @param Model $model
     */
    public function setModel(Model $model);

    /**
     * Takes a list of relation includes and decorates them.
     *
     * @param string[] $includes
     * @param bool     $many        whether the includes are for a collection of results
     * @return array
     */
    public function decorate(array $includes, $many = false);

}
