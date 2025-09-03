<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Package Template Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration options for the package template.
    | You can publish this config file to customize the behavior of the package.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Public ID Type
    |--------------------------------------------------------------------------
    |
    | This option controls the type of public ID that will be generated
    | for models using the HasPublicId trait. Options: 'uuid', 'ulid'
    |
    */
    'public_id_type' => 'uuid',

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Enable or disable specific features of the package.
    |
    */
    'features' => [
        'example_feature' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Integration Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for integrations with other Convertain packages.
    |
    */
    'integrations' => [
        'organizations' => [
            'enabled' => true,
        ],
        'permissions' => [
            'enabled' => true,
        ],
        'checkout' => [
            'enabled' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Routing Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the routing behavior for the package.
    |
    */
    'routes' => [
        'prefix' => '',
        'middleware' => ['web'],
        'locale_prefix' => 'en',
    ],
];
