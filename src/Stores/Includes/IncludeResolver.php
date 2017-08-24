<?php
namespace Czim\DataStore\Stores\Includes;

use Czim\DataStore\Contracts\Resource\ResourceAdapterFactoryInterface;
use Czim\DataStore\Contracts\Resource\ResourceAdapterInterface;
use Czim\DataStore\Contracts\Stores\Includes\IncludeResolverInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use RuntimeException;

class IncludeResolver implements IncludeResolverInterface
{

    /**
     * @var ResourceAdapterFactoryInterface
     */
    protected $adapterFactory;

    /**
     * Active top level model.
     *
     * @var Model
     */
    protected $model;

    /**
     * Adapter for active top level model.
     *
     * @var ResourceAdapterInterface
     */
    protected $adapter;


    /**
     * Sets the resource adapter factory.
     *
     * @param ResourceAdapterFactoryInterface $adapterFactory
     * @return $this
     */
    public function setResourceAdapterFactory(ResourceAdapterFactoryInterface $adapterFactory)
    {
        $this->adapterFactory = $adapterFactory;

        return $this;
    }

    /**
     * Sets the active model and optionally its resource adapter.
     *
     * @param Model                         $model
     * @param ResourceAdapterInterface|null $adapter
     * @return $this
     */
    public function setModel(Model $model, ResourceAdapterInterface $adapter = null)
    {
        $this->model   = $model;
        $this->adapter = $adapter ?: $this->adapterFactory->makeForModel($model);

        return $this;
    }

    /**
     * Takes a list of resource includes and resolves them to eager-loadable include array.
     *
     * @param string[] $includes
     * @return array
     */
    public function resolve(array $includes)
    {
        if (empty($includes) || ! $this->adapterFactory) {
            return [];
        }

        $includesTree = $this->explodeIncludesToNestedArray($includes);

        $resolvedTree = $this->recursivelyResolveEagerLoadingIncludes($this->model, $this->adapter, $includesTree);

        return array_keys(array_dot($resolvedTree));
    }

    /**
     * Returns a fully resolved nested tree of includes.
     *
     * @param Model                    $model
     * @param ResourceAdapterInterface $adapter
     * @param array                    $includesTree
     * @return array
     */
    protected function recursivelyResolveEagerLoadingIncludes(
        Model $model,
        ResourceAdapterInterface $adapter,
        array $includesTree
    ) {
        // @codeCoverageIgnoreStart
        if (empty($includesTree)) {
            return [];
        }
        // @codeCoverageIgnoreEnd

        $resolved = [];

        foreach (array_keys($includesTree) as $include) {

            $relationMethod = $adapter->dataKeyForInclude($include);

            if ( ! $relationMethod) {
                continue;
            }

            // Remember the resolved method for inclusion
            $resolved[ $relationMethod ] = [];

            // If no further nesting, skip
            if (empty($includesTree[ $include ])) {
                continue;
            }

            // Get the model for the included relation
            try {
                $relation = $model->{$relationMethod}();

            } catch (\Exception $e) {

                throw new RuntimeException(
                    "Failed to resolve nested include '{$include}' using relation method '{$relationMethod}'"
                    . " on Model '" . get_class($model) . "'.",
                    $e->getCode(),
                    $e
                );
            }

            if ( ! ($relation instanceof Relation)) {
                throw new RuntimeException(
                    "nested include '{$include}' using relation method '{$relationMethod}'  on Model '"
                    . get_class($model) . "' is not an Eloquent relation method."
                );
            }

            $relatedModel = $relation->getRelated();

            // @codeCoverageIgnoreStart
            if ( ! $relatedModel) {
                continue;
            }
            // @codeCoverageIgnoreEnd

            $relatedAdapter = $this->getResourceAdapterForModel($relatedModel);

            if ( ! $relatedAdapter) {
                continue;
            }

            // Recursively handle the resolution and build the tree
            $resolved[ $relationMethod ] = $this->recursivelyResolveEagerLoadingIncludes(
                $relatedModel,
                $relatedAdapter,
                $includesTree[ $include ]
            );
        }

        return $resolved;
    }

    /**
     * Explodes a list of dot-notated includes to a nested array tree.
     *
     * @param array $includes
     * @return array
     */
    protected function explodeIncludesToNestedArray(array $includes)
    {
        $exploded = [];

        foreach ($includes as $key) {
            array_set($exploded, $key, []);
        }

        return $exploded;
    }

    /**
     * Makes and returns a resource adapter instance for a given model instance.
     *
     * @param Model $model
     * @return ResourceAdapterInterface
     */
    protected function getResourceAdapterForModel(Model $model)
    {
        return $this->adapterFactory->makeForModel($model);
    }

}
