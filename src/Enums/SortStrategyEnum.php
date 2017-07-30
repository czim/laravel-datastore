<?php
namespace Czim\DataStore\Enums;

use MyCLabs\Enum\Enum;

class SortStrategyEnum extends Enum
{
    const ALPHABETIC           = 'alphabetic';
    const ALPHABETIC_NULL_LAST = 'alphabetic-null-last';
    const NUMERIC              = 'numeric';
    const NUMERIC_NULL_LAST    = 'numeric-null-last';
}
