<?php
namespace Czim\DataStore\Contracts\Data;

interface LinkWithPivotDataInterface
{

    /**
     * @return mixed
     */
    public function getKey();

    /**
     * @return array
     */
    public function getPivotAttributes();

}
