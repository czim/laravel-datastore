<?php
namespace Czim\DataStore\Context;

use Czim\DataObject\AbstractDataObject;
use Czim\DataStore\Contracts\Context\ContextInterface;
use Illuminate\Support\Arr;

/**
 * Class RequestContext
 *
 * @property bool      $standard_pagination
 * @property bool      $cursor_pagination
 * @property integer   $page_number
 * @property integer   $page_size
 * @property integer   $page_offset
 * @property integer   $page_limit
 * @property mixed     $page_cursor
 * @property array     $filters
 * @property SortKey[] $sorting
 */
class RequestContext extends AbstractDataObject implements ContextInterface
{

    /**
     * Returns filter data to apply.
     *
     * @return array    associative
     */
    public function filters()
    {
        return $this->filters ?: [];
    }

    /**
     * Returns filter data to apply by key.
     *
     * @param string $key
     * @return mixed
     */
    public function filter($key)
    {
        return Arr::get($this->filters(), $key);
    }

    /**
     * Returns whether the data should be paginated by the standard method.
     *
     * @return bool
     */
    public function shouldBePaginated()
    {
        return $this->standard_pagination
            || null !== $this->pageNumber() && $this->pageSize() && ! $this->cursor_pagination;
    }

    /**
     * Returns whether the data should be paginated by cursor.
     *
     * @return bool
     */
    public function shouldBeCursorPaginated()
    {
        return $this->cursor_pagination || null !== $this->pageCursor();
    }

    /**
     * Returns page number.
     *
     * @return int|null
     */
    public function pageNumber()
    {
        return $this->page_number;
    }

    /**
     * Returns page size.
     *
     * @return int|null
     */
    public function pageSize()
    {
        return $this->page_size;
    }

    /**
     * Returns page cursor value;
     *
     * @return mixed|null
     */
    public function pageCursor()
    {
        return $this->page_cursor;
    }

    /**
     * Returns page offset.
     *
     * @return int|null
     */
    public function pageOffset()
    {
        return $this->page_offset;
    }

    /**
     * Returns page limit.
     *
     * @return int|null
     */
    public function pageLimit()
    {
        return $this->page_limit;
    }

    /**
     * Returns sorting keys to apply in specific order.
     *
     * @return SortKey[]
     */
    public function sorting()
    {
        if ( ! $this->sorting || ! count($this->sorting)) {
            return [];
        }

        $this->sorting = array_map(
            function ($sort) {
                if ($sort instanceof SortKey) {
                    return $sort;
                }
                return new SortKey(ltrim($sort, '-'), substr($sort, 0, 1) == '-');
            },
            $this->sorting
        );

        return $this->sorting;
    }

}
