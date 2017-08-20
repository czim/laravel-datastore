<?php
namespace Czim\DataStore\Stores;

use Czim\DataStore\Contracts\Resource\ResourceAdapterInterface;
use Czim\Repository\Contracts\BaseRepositoryInterface;
use Czim\Repository\Criteria\Common\WithRelations;
use Czim\Repository\Enums\CriteriaKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class EloquentRepositoryDataStore extends AbstractEloquentDataStore
{

    /**
     * @var BaseRepositoryInterface
     */
    protected $repository;

    /**
     * @var ResourceAdapterInterface
     */
    protected $resourceAdapter;

    /**
     * Strategies to use for JSON-API sort names (without '-' prefix).
     *
     * @var string[]
     */
    protected $sortStrategies = [];


    /**
     * Sets the repository to use for accessing data.
     *
     * @param BaseRepositoryInterface $repository
     * @return $this
     */
    public function setRepository(BaseRepositoryInterface $repository)
    {
        $this->repository = $repository;

        return $this;
    }

    /**
     * Returns the used repository.
     *
     * @return BaseRepositoryInterface|null
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * Returns a model instance.
     *
     * @return Model
     */
    public function getModel()
    {
        $model = $this->repository->makeModel(false);

        // @codeCoverageIgnoreStart
        if ($model instanceof Builder) {
            $model = $model->getModel();
        }
        // @codeCoverageIgnoreEnd

        /** @var Model $model */

        return $model;
    }

    /**
     * Returns a fresh query builder for the model.
     *
     * @return \Illuminate\Database\Query\Builder|Builder
     */
    protected function retrieveQuery()
    {
        $this->repository->pushCriteriaOnce(new WithRelations($this->includes), CriteriaKey::WITH);

        return $this->repository->query();
    }

    /**
     * Returns model by ID.
     *
     * @param mixed $id
     * @return mixed
     */
    protected function retrieveById($id)
    {
        $this->repository->pushCriteriaOnce(new WithRelations($this->includes), CriteriaKey::WITH);

        return $this->repository->find($id);
    }

    /**
     * Returns many models by an array of IDs.
     *
     * @param array $ids
     * @return mixed
     */
    protected function retrieveManyById(array $ids)
    {
        $this->repository->pushCriteriaOnce(new WithRelations($this->includes), CriteriaKey::WITH);

        $key = $this->getModel()->getQualifiedKeyName();

        return $this->repository->query()->whereIn($key, $ids)->get();
    }

}
