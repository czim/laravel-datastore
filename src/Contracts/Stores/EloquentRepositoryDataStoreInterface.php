<?php
namespace Czim\DataStore\Contracts\Stores;

use Czim\Repository\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

interface EloquentRepositoryDataStoreInterface
{

    /**
     * Sets the repository to use for accessing data.
     *
     * @param BaseRepositoryInterface $repository
     * @return $this
     */
    public function setRepository(BaseRepositoryInterface $repository);

    /**
     * Returns the used repository.
     *
     * @return BaseRepositoryInterface|null
     */
    public function getRepository();

    /**
     * Returns a model instance.
     *
     * @return Model
     */
    public function getModel();

}
