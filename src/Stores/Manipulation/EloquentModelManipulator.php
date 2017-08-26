<?php
namespace Czim\DataStore\Stores\Manipulation;

use Czim\DataObject\Contracts\DataObjectInterface;
use Czim\DataStore\Contracts\Stores\Manipulation\DataManipulatorInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class EloquentModelManipulator implements DataManipulatorInterface
{

    /**
     * @var Model|null
     */
    protected $model;

    /**
     * The configuration for record manipulation.
     *
     * @var array
     */
    protected $config = [];


    /**
     * @param Model $model
     * @return $this
     */
    public function setModel(Model $model)
    {
        $this->model = $model;

        return $this;
    }

    /**
     * Returns the model set.
     *
     * @return Model|null
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Sets the configuration for record manipulation.
     *
     * @param array $config
     * @return $this
     */
    public function setConfig(array $config)
    {
        $this->config = $config;

        return $this;
    }

    /**
     * Returns the configuration.
     *
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }


    /**
     * Creates a new record with given JSON-API data.
     *
     * @param DataObjectInterface $data
     * @return mixed|false
     */
    public function create(DataObjectInterface $data)
    {
        $model = $this->model;

        return $model::create($data->toArray());
    }

    /**
     * Makes a record without persisting it.
     *
     * @param DataObjectInterface $data
     * @return false|mixed
     */
    public function make(DataObjectInterface $data)
    {
        $model = get_class($this->model);

        return new $model($data->toArray());
    }

    /**
     * Updates a record by ID with given JSON-API data.
     *
     * @param mixed               $id
     * @param DataObjectInterface $data
     * @return bool
     */
    public function updateById($id, DataObjectInterface $data)
    {
        $model = $this->model;

        /** @var Model $record */
        $record = $model::find($id);

        if ( ! $record) {
            throw new ModelNotFoundException();
        }

        return $record->update($data->toArray());
    }

    /**
     * Deletes a record by ID.
     *
     * @param mixed $id
     * @return bool
     */
    public function deleteById($id)
    {
        $model = $this->model;

        return (bool) $model::destroy($id);
    }

    /**
     * Attaches records as related to a given record.
     *
     * @param mixed  $id
     * @param string $relation
     * @param array  $ids
     * @param bool   $detaching
     * @return bool
     */
    public function attachAsRelated($id, $relation, array $ids, $detaching = false)
    {
        // TODO: Implement attachAsRelated() method.
    }

    /**
     * Detaches records as related to a given record.
     *
     * @param mixed  $id
     * @param string $relation
     * @param array  $ids
     * @return bool
     */
    public function detachAsRelated($id, $relation, array $ids)
    {
        // TODO: Implement detachAsRelated() method.
    }

}
