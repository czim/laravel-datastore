<?php
namespace Czim\DataStore\Contracts\Stores;

use Czim\DataStore\Contracts\Resource\ResourceAdapterInterface;
use Czim\DataStore\Contracts\Stores\Manipulation\DataManipulatorInterface;

/**
 * Interface DataStoreInterface
 *
 * The DataStore adapter layer servers as an abstraction to retrieve
 * requested data ready for encoding.
 */
interface DataStoreInterface extends DataStoreRetrieveInterface, DataStoreUpdateInterface
{

    /**
     * Sets the resource adapter.
     *
     * @param ResourceAdapterInterface $resourceAdapter
     * @return $this
     */
    public function setResourceAdapter(ResourceAdapterInterface $resourceAdapter);

    /**
     * Sets the database strategy driver key.
     *
     * @param string $driver
     * @return $this
     */
    public function setStrategyDriver($driver);

    /**
     * Sets the manipulator to use, if any.
     *
     * If no manipulator is set, record manipulation is not supported.
     *
     * @param DataManipulatorInterface|null $manipulator
     * @return $this
     */
    public function setManipulator(DataManipulatorInterface $manipulator = null);

    /**
     * Sets the default page size to use if none specified.
     *
     * @param int $size
     * @return $this
     */
    public function setDefaultPageSize($size);

}
