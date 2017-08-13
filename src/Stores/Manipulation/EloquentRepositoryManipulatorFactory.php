<?php
namespace Czim\DataStore\Stores\Manipulation;

use Czim\DataStore\Contracts\Stores\Manipulation\DataManipulatorInterface;
use Czim\Repository\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class EloquentRepositoryManipulatorFactory extends EloquentModelManipulatorFactory
{

    /**
     * Returns a manipulator for a given model repository.
     *
     * @param BaseRepositoryInterface $repository
     * @return DataManipulatorInterface
     */
    public function makeForRepository(BaseRepositoryInterface $repository)
    {
        $model = $repository->makeModel(false);

        // @codeCoverageIgnoreStart
        if ($model instanceof EloquentBuilder) {
            $model = $model->getModel();
        }
        // @codeCoverageIgnoreEnd

        /** @var Model $model */

        /** @var EloquentRepositoryManipulator $instance */
        $instance = app($this->getManipulatorClassForRepositoryClass(get_class($repository)));

        $instance->setModel($model);
        $instance->setRepository($repository);
        $instance->setConfig($this->getConfigForModelClass(get_class($model)));

        return $instance;
    }

    /**
     * Returns a manipulator for a given model.
     *
     * @param Model $model
     * @return DataManipulatorInterface
     */
    public function makeForModel(Model $model)
    {
        throw new RuntimeException('Not implemented');
    }

    /**
     * @param string $class
     * @return string
     */
    protected function getManipulatorClassForRepositoryClass($class)
    {
        return config("datastore.manipulation.class.{$class}", $this->getDefaultManipulatorClass());
    }

    /**
     * @return string
     */
    protected function getDefaultManipulatorClass()
    {
        return EloquentRepositoryManipulator::class;
    }

}
