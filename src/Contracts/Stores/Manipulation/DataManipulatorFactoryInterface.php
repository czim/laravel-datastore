<?php
namespace Czim\DataStore\Contracts\Stores\Manipulation;

use Czim\Repository\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

interface DataManipulatorFactoryInterface
{

    /**
     * Returns a manipulator for a given model.
     *
     * @param Model $model
     * @return DataManipulatorInterface
     */
    public function makeForModel(Model $model);

    /**
     * Returns a manipulator for a given model repository.
     *
     * @param BaseRepositoryInterface $repository
     * @return DataManipulatorInterface
     */
    public function makeForRepository(BaseRepositoryInterface $repository);

}
