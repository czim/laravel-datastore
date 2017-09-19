<?php
namespace Czim\DataStore\Contracts\Stores\Includes;

use Czim\DataStore\Contracts\Resource\ResourceAdapterFactoryInterface;
use Czim\DataStore\Contracts\Resource\ResourceAdapterInterface;
use Illuminate\Database\Eloquent\Model;

interface IncludeResolverInterface
{

    /**
     * Takes a list of resource includes and resolves them to eager-loadable include array.
     *
     * @param string[] $includes
     * @return array
     */
    public function resolve(array $includes);

    /**
     * Sets the resource adapter factory.
     *
     * @param ResourceAdapterFactoryInterface $adapterFactory
     * @return $this
     */
    public function setResourceAdapterFactory(ResourceAdapterFactoryInterface $adapterFactory);

    /**
     * Sets the active model and optionally its resource adapter.
     *
     * @param Model                         $model
     * @param ResourceAdapterInterface|null $adapter
     * @return $this
     */
    public function setModel(Model $model, ResourceAdapterInterface $adapter = null);

}
