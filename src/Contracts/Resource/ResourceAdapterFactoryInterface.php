<?php
namespace Czim\DataStore\Contracts\Resource;

use Czim\Repository\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

interface ResourceAdapterFactoryInterface
{

    /**
     * Returns a resource adapter for a given model.
     *
     * @param Model $model
     * @return ResourceAdapterInterface
     */
    public function makeForModel(Model $model);

    /**
     * Returns a resource adapter for a given model repository.
     *
     * @param BaseRepositoryInterface $repository
     * @return ResourceAdapterInterface
     */
    public function makeForRepository(BaseRepositoryInterface $repository);

}
