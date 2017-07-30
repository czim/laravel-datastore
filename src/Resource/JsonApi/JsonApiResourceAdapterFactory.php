<?php
namespace Czim\DataStore\Resource\JsonApi;

use Czim\DataStore\Contracts\Resource\ResourceAdapterInterface;
use Czim\DataStore\Resource\AbstractResourceAdapterFactory;
use Czim\JsonApi\Contracts\Repositories\ResourceRepositoryInterface;
use Czim\JsonApi\Contracts\Resource\EloquentResourceInterface;
use Illuminate\Database\Eloquent\Model;
use UnexpectedValueException;

class JsonApiResourceAdapterFactory extends AbstractResourceAdapterFactory
{

    /**
     * @var ResourceRepositoryInterface
     */
    protected $resourceRepository;

    /**
     * @param ResourceRepositoryInterface $resourceRepository
     */
    public function __construct(ResourceRepositoryInterface $resourceRepository)
    {
        $this->resourceRepository = $resourceRepository;
    }

    /**
     * Returns a resource adapter for a given model.
     *
     * @param Model $model
     * @return ResourceAdapterInterface
     */
    public function makeForModel(Model $model)
    {
        return new JsonApiEloquentResourceAdapter($this->getResourceForModel($model));
    }

    /**
     * @param Model $model
     * @return EloquentResourceInterface
     */
    protected function getResourceForModel(Model $model)
    {
        $resource = $this->resourceRepository->getByModel($model);

        if ( ! ($resource instanceof EloquentResourceInterface)) {
            throw new UnexpectedValueException('Expected EloquentResourceInterface, got ' . get_class($resource));
        }

        return $resource;
    }

}
