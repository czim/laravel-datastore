<?php
namespace Czim\DataStore\Strategies\Filter;

use App\Enums\FilterStrategyEnum;
use Illuminate\Database\Eloquent\Builder;
use UnexpectedValueException;

class EloquentFilter
{

    /**
     * @var Builder
     */
    protected $query;

    /**
     * @var string
     */
    protected $key;

    /**
     * @var mixed
     */
    protected $value;


    /**
     * @param Builder $query
     */
    public function __construct(Builder $query)
    {
        $this->query = $query;
    }

    /**
     * @param string $strategy
     * @param string $key
     * @param mixed  $value
     * @return Builder
     */
    public function apply($strategy, $key, $value)
    {
        $this->key   = $key;
        $this->value = $value;

        switch ($strategy) {

            case FilterStrategyEnum::EXACT:
                $this->applyExactFilter();
                break;

            case FilterStrategyEnum::EXACT_CASE_INSENSITIVE:
                $this->applyExactCaseInsensitiveFilter();
                break;

            case FilterStrategyEnum::LIKE:
                $this->applyLikeFilter();
                break;

            case FilterStrategyEnum::LIKE_CASE_INSENSITIVE:
                $this->applyLikeCaseInsensitiveFilter();
                break;

            default:
                throw new UnexpectedValueException("Unhandled filter strategy '{$strategy}'");
        }

        return $this->query;
    }


    protected function applyExactFilter()
    {
        if (is_array($this->value)) {
            $this->query->whereIn($this->key, $this->value);
        } else {
            $this->query->where($this->key, '=', $this->value);
        }
    }

    protected function applyExactCaseInsensitiveFilter()
    {
        if (is_array($this->value)) {
            $this->query->whereIn($this->key, $this->value);
        } else {
            $this->query->where($this->key, '=', strtolower($this->value));
        }
    }

    protected function applyLikeFilter()
    {
        $this->query->where($this->key, 'like', '%' . $this->value . '%');
    }

    protected function applyLikeCaseInsensitiveFilter()
    {
        $this->query->where($this->key, 'like', '%' . strtolower($this->value) . '%');
    }

}
