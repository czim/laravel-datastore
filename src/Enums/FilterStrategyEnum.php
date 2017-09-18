<?php
namespace Czim\DataStore\Enums;

use MyCLabs\Enum\Enum;

class FilterStrategyEnum extends Enum
{
    const EXACT                  = 'exact';
    const EXACT_CASE_INSENSITIVE = 'exact-case-insensitive';
    const EXACT_COMMA_SEPARATED  = 'exact-comma-separated';
    const LIKE                   = 'like';
    const LIKE_CASE_INSENSITIVE  = 'like-case-insensitive';

    const RELATION_SINGULAR = 'relation-singular';
    const RELATION_PLURAL   = 'relation-plural';
    const RELATION_MORPH    = 'relation-morph';
}
