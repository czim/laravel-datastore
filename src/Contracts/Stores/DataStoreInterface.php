<?php
namespace Czim\DataStore\Contracts\Stores;

/**
 * Interface DataStoreInterface
 *
 * The DataStore adapter layer servers as an abstraction to retrieve
 * requested data ready for encoding.
 */
interface DataStoreInterface extends DataStoreRetrieveInterface, DataStoreUpdateInterface
{

    /**
     * Sets the default page size to use if none specified.
     *
     * @param int $size
     * @return $this
     */
    public function setDefaultPageSize($size);

}
