<?php

use Czim\DataStore\Enums\FilterStrategyEnum;
use Czim\DataStore\Enums\SortStrategyEnum;
use Czim\DataStore\Stores\Filtering\Strategies as FilterStrategies;
use Czim\DataStore\Stores\Sorting\Strategies as SortStrategies;

return [

    /*
    |--------------------------------------------------------------------------
    | Drivers
    |--------------------------------------------------------------------------
    |
    | Abstraction configuration for different database drivers.
    |
    */

    'drivers' => [

        'datastore' => [
            'default' => 'model',

            'drivers' => [

                'model' => [
                    // Datastore class name
                    'datastore' => Czim\DataStore\Stores\EloquentRepositoryDataStore::class,
                    // Driver key for resource adapter (null for default)
                    'adapter'   => null,
                    // Driver key for database (null for default)
                    'database'  => null,
                ],

                'repository' => [
                    'datastore' => Czim\DataStore\Stores\EloquentRepositoryDataStore::class,
                    'adapter'   => null,
                ],
            ],
        ],

        // The resource adaptor layer
        'adapter' => [
            'default' => 'jsonapi',

            'drivers' => [

                'jsonapi' => [
                    'factory' => \Czim\DataStore\Resource\JsonApi\JsonApiResourceAdapterFactory::class,
                ],
            ],
        ],

        'strategies' => [
            'default' => 'mysql',

            'filtering' => [

                'mysql'   => [
                    'default' => FilterStrategies\LikeStrategy::class,
                    'map'     => [
                        FilterStrategyEnum::EXACT                  => FilterStrategies\ExactStrategy::class,
                        FilterStrategyEnum::EXACT_CASE_INSENSITIVE => FilterStrategies\ExactStrategy::class,
                        FilterStrategyEnum::EXACT_COMMA_SEPARATED  => FilterStrategies\ExactCommaSeparatedStrategy::class,
                        FilterStrategyEnum::LIKE                   => FilterStrategies\LikeStrategy::class,
                        FilterStrategyEnum::LIKE_CASE_INSENSITIVE  => FilterStrategies\LikeStrategy::class,
                    ],
                ],

                'postgres' => [
                    'default' => FilterStrategies\Postgres\LikeCaseInsensitiveStrategy::class,
                    'map'     => [
                        FilterStrategyEnum::EXACT                  => FilterStrategies\ExactStrategy::class,
                        FilterStrategyEnum::EXACT_CASE_INSENSITIVE => FilterStrategies\Postgres\ExactCaseInsensitiveStrategy::class,
                        FilterStrategyEnum::EXACT_COMMA_SEPARATED  => FilterStrategies\ExactCommaSeparatedStrategy::class,
                        FilterStrategyEnum::LIKE                   => FilterStrategies\LikeStrategy::class,
                        FilterStrategyEnum::LIKE_CASE_INSENSITIVE  => FilterStrategies\Postgres\LikeCaseInsensitiveStrategy::class,
                    ],
                ],

                'sqlite' => [
                    'default' => FilterStrategies\LikeStrategy::class,
                    'map'     => [
                        FilterStrategyEnum::EXACT                  => FilterStrategies\ExactStrategy::class,
                        FilterStrategyEnum::EXACT_CASE_INSENSITIVE => FilterStrategies\ExactStrategy::class,
                        FilterStrategyEnum::EXACT_COMMA_SEPARATED  => FilterStrategies\ExactCommaSeparatedStrategy::class,
                        FilterStrategyEnum::LIKE                   => FilterStrategies\LikeStrategy::class,
                        FilterStrategyEnum::LIKE_CASE_INSENSITIVE  => FilterStrategies\LikeStrategy::class,
                    ],
                ],
            ],

            'sorting' => [

                'mysql'   => [
                    'handler' => \Czim\DataStore\Stores\Sorting\EloquentSorter::class,
                    'map'     => [
                        FilterStrategyEnum::EXACT                  => FilterStrategies\ExactStrategy::class,
                        FilterStrategyEnum::EXACT_CASE_INSENSITIVE => FilterStrategies\ExactStrategy::class,
                        FilterStrategyEnum::LIKE                   => FilterStrategies\LikeStrategy::class,
                        FilterStrategyEnum::LIKE_CASE_INSENSITIVE  => FilterStrategies\LikeStrategy::class,
                    ],
                ],

            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Filtering
    |--------------------------------------------------------------------------
    |
    | Defines the filter strategies to be used for model attributes.
    |
    | Strategies itself is keyed by model class name, and values for those keys should be
    | an associative array mapping attribute names to their respective strategies. E.g:
    |
    |      'strategies' => [
    |          \YourModel\ClassName::class => [
    |              'attribute-name' => 'strategy-alias',
    |          ],
    |      ],
    |
    | Note that the data attribute keys here should refer to f.i. Eloquent model attributes,
    | *not* JSON-API data keys!
    |
    */

    'filter' => [

        // The default / fallback strategy
        'default' => FilterStrategyEnum::LIKE_CASE_INSENSITIVE,

        // Default strategies per attribute key
        'default-strategies' => [
            'id'   => FilterStrategyEnum::EXACT,
            'slug' => FilterStrategyEnum::EXACT_CASE_INSENSITIVE,
        ],

        // Strategies per model
        'strategies' => [

            //App\Models\YourModel::class => [
            //],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Sorting
    |--------------------------------------------------------------------------
    |
    | Defines the sort strategies to be used for model attributes.
    |
    | Strategies itself is keyed by model class name, and values for those keys should be
    | an associative array mapping attribute names to their respective strategies. E.g:
    |
    |      'strategies' => [
    |          \YourModel\ClassName::class => [
    |              'attribute-name' => 'strategy-alias',
    |          ],
    |      ],
    |
    | Note that the data attribute keys here should refer to f.i. Eloquent model attributes,
    | *not* JSON-API data keys!
    |
    */

    'sort' => [

        // The default / fallback strategy
        'default' => SortStrategyEnum::ALPHABETIC,

        // Default strategies per data attribute key
        'default-strategies' => [
            'id'         => SortStrategyEnum::NUMERIC,
            'active'     => SortStrategyEnum::NUMERIC,
            'position'   => SortStrategyEnum::NUMERIC,
            'created_at' => SortStrategyEnum::NUMERIC,
            'updated_at' => SortStrategyEnum::NUMERIC,
        ],

        // Strategies per model
        'strategies' => [

            //App\Models\YourModel::class => [
            //],
        ],
    ],

];
