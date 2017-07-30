<?php
namespace Czim\DataStore\Contracts\Resource;

/**
 * Interface ResourceAdapterInterface
 *
 * Adapter layer between data store and chosen API data representation.
 */
interface ResourceAdapterInterface
{

    /**
     * Returns the data storage key to use for a given incoming attribute.
     *
     * @param string $attribute
     * @return string
     */
    public function dataKeyForAttribute($attribute);

    /**
     * Returns available include keys.
     *
     * @return string[]
     */
    public function availableIncludeKeys();

    /**
     * Returns default include keys.
     *
     * @return string[]
     */
    public function defaultIncludes();

    /**
     * Returns whether included relation is for a singular relationship.
     *
     * @param string $include
     * @return bool
     */
    public function isIncludeSingular($include);

    /**
     * Returns the available filter keys.
     *
     * @return string[]
     */
    public function availableFilterKeys();

    /**
     * Returns the default filters.
     *
     * @return array|null    associative by filter keys, null if none set
     */
    public function defaultFilters();

    /**
     * Returns available sorting keys (without direction).
     *
     *
     * @return string[]
     */
    public function availableSortKeys();

    /**
     * Returns default sorting keys (with direction).
     *
     * @return string[]
     */
    public function defaultSorting();

}
