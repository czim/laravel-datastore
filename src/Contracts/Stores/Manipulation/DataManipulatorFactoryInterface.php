<?php
namespace Czim\DataStore\Contracts\Stores\Manipulation;

interface DataManipulatorFactoryInterface
{

    /**
     * Makes a manipulator for a given object instance.
     *
     * @param object $object
     * @return DataManipulatorInterface
     */
    public function makeForObject($object);

}
