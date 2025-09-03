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
    'public_id_type' => env('PACKAGE_PUBLIC_ID_TYPE', 'uuid'),

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Enable or disable specific features of the package.
    |
    */
    'features' => [
        'example_feature' => env('PACKAGE_EXAMPLE_FEATURE', true),
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
            'enabled' => env('PACKAGE_ORGANIZATIONS_ENABLED', true),
        ],
        'permissions' => [
            'enabled' => env('PACKAGE_PERMISSIONS_ENABLED', true),
        ],
        'checkout' => [
            'enabled' => env('PACKAGE_CHECKOUT_ENABLED', true),
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
        'prefix' => env('PACKAGE_ROUTE_PREFIX', ''),
        'middleware' => ['web'],
        'locale_prefix' => env('PACKAGE_LOCALE_PREFIX', 'en'),
    ],
];
