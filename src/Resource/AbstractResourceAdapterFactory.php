<?php
namespace Czim\DataStore\Resource;

use Czim\DataStore\Contracts\Resource\ResourceAdapterFactoryInterface;
use Czim\DataStore\Contracts\Resource\ResourceAdapterInterface;
use Czim\Repository\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;

abstract class AbstractResourceAdapterFactory implements ResourceAdapterFactoryInterface
{

    /**
     * Returns a resource adapter for a given model repository.
     *
     * @param BaseRepositoryInterface $repository
     * @return ResourceAdapterInterface
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
        return $this->makeForModel($model);
    }

}
