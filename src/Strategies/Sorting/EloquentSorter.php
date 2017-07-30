<?php
namespace Czim\DataStore\Strategies\Sorting;

use App\Enums\SortStrategyEnum;
use Illuminate\Database\Eloquent\Builder;
use UnexpectedValueException;

class EloquentSorter
{

    /**
     * @var Builder
     */
    protected $query;

    /**
     * @var string
     */
    protected $attribute;

    /**
     * @var bool
     */
    protected $reverse;


    /**
     * @param Builder $query
     */
    public function __construct(Builder $query)
    {
        $this->query = $query;
    }

    /**
     * @param string $strategy
     * @param string $attribute
     * @param bool   $reverse
     * @return Builder
     */
    public function apply($strategy, $attribute, $reverse = false)
    {
        $this->attribute = $attribute;
        $this->reverse   = (bool) $reverse;

        switch ($strategy) {

            case SortStrategyEnum::ALPHABETIC:
                $this->applyAlphabeticSort();
                break;

            case SortStrategyEnum::NUMERIC:
                $this->applyNumericSort();
                break;

            default:
                throw new UnexpectedValueException("Unhandled sorting strategy '{$strategy}'");
        }

        return $this->query;
    }


    protected function applyAlphabeticSort()
    {
        $this->query->orderBy($this->attribute, $this->direction());
    }

    protected function applyNumericSort()
    {
        $this->applyBasicSort();
    }

    protected function applyBasicSort()
    {
        $this->query->orderBy($this->attribute, $this->direction());
    }

    /**
     * Returns 'asc' or 'desc' for sort direction.
     *
     * @return string
     */
    protected function direction()
    {
        return $this->reverse ? 'desc' : 'asc';
    }

}
