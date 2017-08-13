<?php
namespace Czim\DataStore\Contracts\Stores\Manipulation;

use Czim\DataStore\Contracts\Stores\DataStoreUpdateInterface;

interface DataManipulatorInterface extends DataStoreUpdateInterface
{

    /**
     * Sets the configuration for record manipulation.
     *
     * @param array $config
     * @return $this
     */
    public function setConfig(array $config);

    /**
     * Returns the configuration.
     *
     * @return array
     */
    public function getConfig();

}
