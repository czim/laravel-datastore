<?php
namespace Czim\DataStore\Data;

use Czim\DataObject\AbstractDataObject;
use Czim\DataStore\Contracts\Data\LinkWithPivotDataInterface;

/**
 * Class LinkWithPivotData
 *
 * Container for link data for a belongs-to-many relationship with pivot attributes.
 *
 * @property mixed $key
 * @property array $pivot   Pivot attributes as associative key/value array
 */
class LinkWithPivotData extends AbstractDataObject implements LinkWithPivotDataInterface
{

    protected $rules = [
        'key'   => 'required',
        'pivot' => 'array',
    ];

    /**
     * @return mixed
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return array
     */
    public function getPivotAttributes()
    {
        return $this->pivot ?: [];
    }

}
