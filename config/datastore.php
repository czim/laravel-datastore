<?php

use Czim\DataStore\Enums\FilterStrategyEnum;
use Czim\DataStore\Enums\SortStrategyEnum;
use Czim\DataStore\Strategies;

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

        // The resource adaptor layer
        'adapter' => [
            'default' => 'jsonapi',

            'drivers' => [

                'jsonapi' => [
                    'factory' => \Czim\DataStore\Resource\JsonApi\JsonApiResourceAdapterFactory::class,
                ],
            ],
        ],

        'database' => [
            'default' => 'mysql',

            'drivers' => [

                'mysql' => [
                    'filter' => Strategies\Filter\EloquentFilter::class,
                    'sorter' => Strategies\Sorting\EloquentSorter::class,
                ],

                'postgres' => [
                    'filter' => Strategies\Filter\EloquentPostgresFilter::class,
                    'sorter' => Strategies\Sorting\EloquentPostgresSorter::class,
                ],

                'sqlite' => [
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
