<?php
namespace Czim\DataStore\Contracts\Stores;

use Czim\DataStore\Contracts\Context\ContextInterface;

interface DataStoreRetrieveInterface
{

    /**
     * Returns data by single ID.
     *
     * @param mixed $id
     * @param array $includes
     * @return mixed
     */
    public function getById($id, $includes = []);

    /**
     * Returns data by set of IDs.
     *
     * @param mixed[] $ids
     * @param array   $includes
     * @return mixed
     */
    public function getManyById(array $ids, $includes = []);

    /**
     * Returns data by given context.
     *
     * @param ContextInterface $context
     * @param array   $includes
     * @return mixed
     */
    public function getByContext(ContextInterface $context, $includes = []);

}
