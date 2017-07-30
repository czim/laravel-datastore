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
     * Updates a record by ID with given JSON-API data.
     *
     * @param string $id
     * @param DataObjectInterface $data
     * @return bool
     */
    public function updatedById($id, DataObjectInterface $data);

    /**
     * Deletes a record by ID.
     *
     * @param string $id
     * @return bool
     */
    public function deleteById($id);

}
