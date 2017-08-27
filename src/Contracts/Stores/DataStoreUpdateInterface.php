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
     * Attaches or replaces records for a relationship.
     *
     * @param mixed         $parent
     * @param string        $relation
     * @param mixed|mixed[] $records
     * @param bool          $detaching  if true, everything but the given records are detached
     * @return bool
     */
    public function attachRelatedRecords($parent, $relation, $records, $detaching = false);

    /**
     * Detaches records by ID for a relationship.
     *
     * @param mixed         $parent
     * @param string        $relation
     * @param mixed|mixed[] $records
     * @return bool
     */
    public function detachRelatedRecords($parent, $relation, $records);

    /**
     * Detaches records by ID for a relationship.
     *
     * @param mixed         $parent
     * @param string        $relation
     * @param mixed|mixed[] $ids
     * @return bool
     */
    public function detachRelatedRecordsById($parent, $relation, $ids);

}
