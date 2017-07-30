<?php
namespace Czim\DataStore\Contracts\Context;

interface PaginationContextInterface
{

    /**
     * Returns whether the data should be paginated by the standard method.
     *
     * @return bool
     */
    public function shouldBePaginated();

    /**
     * Returns whether the data should be paginated by cursor.
     *
     * @return bool
     */
    public function shouldBeCursorPaginated();

    /**
     * Returns page number.
     *
     * @return int|null
     */
    public function pageNumber();

    /**
     * Returns page size.
     *
     * @return int|null
     */
    public function pageSize();

    /**
     * Returns page cursor value;
     *
     * @return mixed|null
     */
    public function pageCursor();


    /**
     * Returns page offset.
     *
     * @return int|null
     */
    public function pageOffset();

    /**
     * Returns page limit.
     *
     * @return int|null
     */
    public function pageLimit();

}
