<?php
namespace Czim\DataStore\Contracts\Context;

interface ContextInterface extends
    PaginationContextInterface,
    FilterContextInterface,
    SortingContextInterface
{
}
