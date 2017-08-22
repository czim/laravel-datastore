<?php
namespace Czim\DataStore\Contracts\Stores\Includes;

use Czim\DataStore\Contracts\Resource\ResourceAdapterFactoryInterface;

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

}
