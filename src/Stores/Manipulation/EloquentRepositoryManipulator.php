<?php
namespace Czim\DataStore\Stores\Manipulation;

use Czim\DataObject\Contracts\DataObjectInterface;
use Czim\Repository\Contracts\BaseRepositoryInterface;

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
     * Updates a record by ID with given JSON-API data.
     *
     * @param string              $id
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
     * @param string $id
     * @return bool
     */
    public function deleteById($id)
    {
        return $this->repository->delete($id);
    }

    /**
     * Attaches records as related to a given record.
     *
     * @param mixed  $id
     * @param string $relation
     * @param array  $ids
     * @param bool   $detaching
     * @return bool
     */
    public function attachAsRelated($id, $relation, array $ids, $detaching = false)
    {
        // TODO: Implement attachAsRelated() method.
    }

    /**
     * Detaches records as related to a given record.
     *
     * @param mixed  $id
     * @param string $relation
     * @param array  $ids
     * @return bool
     */
    public function detachAsRelated($id, $relation, array $ids)
    {
        // TODO: Implement detachAsRelated() method.
    }

}
