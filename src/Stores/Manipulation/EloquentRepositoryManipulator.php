<?php
namespace Czim\DataStore\Stores\Manipulation;

use Czim\DataObject\Contracts\DataObjectInterface;
use Czim\Repository\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

class EloquentRepositoryManipulator extends EloquentModelManipulator
{

    /**
     * @var BaseRepositoryInterface|null
     */
    protected $repository;


    /**
     * @param BaseRepositoryInterface $repository
     * @return $this
     */
    public function setRepository(BaseRepositoryInterface $repository)
    {
        $this->repository = $repository;

        return $this;
    }

    /**
     * @return BaseRepositoryInterface|null
     */
    public function getRepository()
    {
        return $this->repository;
    }


    /**
     * Creates a new record with given JSON-API data.
     *
     * @param DataObjectInterface $data
     * @return mixed|false
     */
    public function create(DataObjectInterface $data)
    {
        return $this->repository->create($data->toArray());
    }

    /**
     * Makes a record without persisting it.
     *
     * @param DataObjectInterface $data
     * @return false|mixed
     */
    public function make(DataObjectInterface $data)
    {
        $model = $this->repository->makeModel(false);

        // @codeCoverageIgnoreStart
        if ($model instanceof Builder) {
            $model = $this->getModel();
        }
        // @codeCoverageIgnoreEnd

        $model->fill($data->toArray());

        return $model;
    }

    /**
     * Updates a record by ID with given JSON-API data.
     *
     * @param mixed               $id
     * @param DataObjectInterface $data
     * @return bool
     */
    public function updateById($id, DataObjectInterface $data)
    {
        return $this->repository->update($data->toArray(), $id);
    }

    /**
     * Deletes a record by ID.
     *
     * @param mixed $id
     * @return bool
     */
    public function deleteById($id)
    {
        return $this->repository->delete($id);
    }

}
