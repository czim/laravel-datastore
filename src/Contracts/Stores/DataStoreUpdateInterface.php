<?php
namespace Czim\DataStore\Contracts\Stores;

use Czim\DataObject\Contracts\DataObjectInterface;

interface DataStoreUpdateInterface
{

    /**
     * Creates a new record with given JSON-API data.
     *
     * @param DataObjectInterface $data
     * @return mixed|false
     */
    public function create(DataObjectInterface $data);

    /**
     * Makes a record without persisting it.
     *
     * @param DataObjectInterface $data
     * @return false|mixed
     */
    public function make(DataObjectInterface $data);

    /**
     * Updates a record by ID with given JSON-API data.
     *
     * @param string $id
     * @param DataObjectInterface $data
     * @return bool
     */
    public function updateById($id, DataObjectInterface $data);

    /**
     * Deletes a record by ID.
     *
     * @param string $id
     * @return bool
     */
    public function deleteById($id);

    /**
     * Attaches records as related to a given record.
     *
     * @param mixed  $id
     * @param string $relation
     * @param array  $ids
     * @param bool   $detaching
     * @return bool
     */
    public function attachAsRelated($id, $relation, array $ids, $detaching = false);

    /**
     * Detaches records as related to a given record.
     *
     * @param mixed  $id
     * @param string $relation
     * @param array  $ids
     * @return bool
     */
    public function detachAsRelated($id, $relation, array $ids);

}
