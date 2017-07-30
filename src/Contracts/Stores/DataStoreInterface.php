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
}
