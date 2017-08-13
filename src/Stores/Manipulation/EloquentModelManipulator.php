<?php
namespace Czim\DataStore\Stores\Manipulation;

use Czim\DataObject\Contracts\DataObjectInterface;
use Czim\DataStore\Contracts\Stores\DataStoreUpdateInterface;

/**
 * Class EloquentModelManipulator
 *
 * By default, if there is no data available on what keys may be
 * updated, the model guarding/fillable logic should be used for attributes
 */
class EloquentModelManipulator implements DataStoreUpdateInterface
{


    /**
     * Creates a new record with given JSON-API data.
     *
     * @param DataObjectInterface $data
     * @return mixed|false
     */
    public function create(DataObjectInterface $data)
    {
        // TODO: Implement create() method.
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
        // TODO: Implement updateById() method.
    }

    /**
     * Deletes a record by ID.
     *
     * @param string $id
     * @return bool
     */
    public function deleteById($id)
    {
        // TODO: Implement deleteById() method.
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
