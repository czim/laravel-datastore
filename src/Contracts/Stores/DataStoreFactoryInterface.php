<?php
namespace Czim\DataStore\Contracts\Stores;

interface DataStoreFactoryInterface
{

    /**
     * Sets the driver for the next make call.
     *
     * @param string|null $driver
     * @return $this
     */
    public function driver($driver);

    /**
     * Sets the configuration for the next make call.
     *
     * @param array $config
     * @return $this
     */
    public function config(array $config = []);

    /**
     * Makes a data store for a given object (type).
     *
     * @param object $object
     * @return DataStoreInterface
     */
    public function makeForObject($object);

}
