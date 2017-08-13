<?php
namespace Czim\DataStore\Stores\Manipulation;

use Czim\DataStore\Contracts\Stores\Manipulation\DataManipulatorFactoryInterface;
use Czim\DataStore\Contracts\Stores\Manipulation\DataManipulatorInterface;
use Czim\Repository\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class EloquentModelManipulatorFactory implements DataManipulatorFactoryInterface
{

    /**
     * Makes a manipulator for a given object instance.
     *
     * @param object $object
     * @return DataManipulatorInterface
     */
    public function makeForObject($object)
    {
        if ($object instanceof Model) {
            return $this->makeForModel($object);
        }

        if ($object instanceof BaseRepositoryInterface) {
            return $this->makeForRepository($object);
        }

        throw new RuntimeException('Unknown object type');
    }

    /**
     * Returns a manipulator for a given model repository.
     *
     * @param BaseRepositoryInterface $repository
     * @return DataManipulatorInterface
     */
    protected function makeForRepository(BaseRepositoryInterface $repository)
    {
        $model = $repository->makeModel(false);

        // @codeCoverageIgnoreStart
        if ($model instanceof EloquentBuilder) {
            $model = $model->getModel();
        }
        // @codeCoverageIgnoreEnd

        /** @var Model $model */
        return $this->makeForModel($model);
    }

    /**
     * Returns a manipulator for a given model.
     *
     * @param Model $model
     * @return DataManipulatorInterface
     */
    protected function makeForModel(Model $model)
    {
        /** @var EloquentModelManipulator $instance */
        $instance = app($this->getManipulatorClassForModelClass(get_class($model)));

        $instance->setModel($model);
        $instance->setConfig($this->getConfigForModelClass(get_class($model)));

        return $instance;
    }

    /**
     * @param string $class
     * @return string
     */
    protected function getManipulatorClassForModelClass($class)
    {
        return config("datastore.manipulation.class.{$class}", $this->getDefaultManipulatorClass());
    }

    /**
     * @return string
     */
    protected function getDefaultManipulatorClass()
    {
        return EloquentModelManipulator::class;
    }

    /**
     * @param string $class
     * @return array
     */
    protected function getConfigForModelClass($class)
    {
        return config("datastore.manipulation.config.model.{$class}", $this->getDefaultManipulatorConfig()) ?: [];
    }

    /**
     * @return array
     */
    protected function getDefaultManipulatorConfig()
    {
        return config('datastore.manipulation.config.default');
    }

}
