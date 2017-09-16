<?php
namespace Czim\DataStore\Test\Helpers\Stores\Includes;

use Czim\DataStore\Contracts\Stores\Includes\IncludeDecoratorInterface;
use Illuminate\Database\Eloquent\Model;

class TestIncludeDecorator implements IncludeDecoratorInterface
{

    /**
     * @var Model
     */
    protected $model;

    /**
     * Sets the parent model to which the top-level includes are related.
     *
     * @param Model $model
     * @return $this
     */
    public function setModel(Model $model)
    {
        $this->model;

        return $this;
    }

    /**
     * Takes a list of relation includes and decorates them.
     *
     * @param string[] $includes
     * @param bool     $many
     * @return array
     */
    public function decorate(array $includes, $many = false)
    {
        if ($many) {
            $include[] = 'testingMany';
        } else {
            $include[] = 'testingSingle';
        }

        return $includes;
    }

}
