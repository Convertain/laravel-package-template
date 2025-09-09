<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Onboarding Enabled
    |--------------------------------------------------------------------------
    |
    | This option controls whether the onboarding flow is enabled for new users.
    | When disabled, users will bypass onboarding completely.
    |
    */
    'enabled' => env('ONBOARDING_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Template Mode
    |--------------------------------------------------------------------------
    |
    | Controls the template style used for onboarding pages.
    | Options: 'minimal', 'ultra-minimal', 'standard'
    |
    */
    'template_mode' => env('ONBOARDING_TEMPLATE_MODE', 'minimal'),

    /*
    |--------------------------------------------------------------------------
    | Steps Configuration
    |--------------------------------------------------------------------------
    |
    | Configure which onboarding steps are enabled and their settings.
    | Steps can be conditionally enabled based on installed packages.
    |
    */
    'steps' => [
        'personal' => [
            'enabled' => true,
            'required' => true,
            'skip_if_complete' => env('ONBOARDING_SKIP_COMPLETE_PROFILE', true),
            'allow_editing' => true,
            'fields' => [
                'first_name' => ['required' => true],
                'last_name' => ['required' => true],
                'email' => ['required' => true, 'readonly' => true],
                'country' => ['required' => true],
                'state' => ['required_if' => 'country:US'],
                'phone' => ['required' => false],
            ],
            'faq_group' => env('ONBOARDING_FAQ_GROUP_PERSONAL'),
        ],

        'email_verification' => [
            'enabled' => env('ONBOARDING_REQUIRE_EMAIL_VERIFICATION', false),
            'method' => env('ONBOARDING_VERIFICATION_METHOD', 'code'), // 'code' or 'link'
            'code_length' => 6,
            'code_expiry_minutes' => 10,
            'max_attempts' => 5,
            'resend_delay_seconds' => 60,
        ],

        'phone_verification' => [
            'enabled' => env('ONBOARDING_REQUIRE_PHONE_VERIFICATION', false),
            'required' => false,
            'code_length' => 6,
            'code_expiry_minutes' => 10,
            'voice_fallback' => true,
            'max_attempts' => 5,
            'resend_delay_seconds' => 60,
        ],

        'organization' => [
            'enabled' => 'auto-detect', // Will check for laravel-organizations package
            'required' => true,
            'allow_skip' => env('ONBOARDING_ALLOW_SKIP_ORGANIZATION', false),
            'fields' => [
                'organization_name' => ['required' => true],
                'organization_country' => ['required' => true],
                'organization_state' => ['required_if' => 'organization_country:US'],
            ],
            'default_role' => 'admin',
            'faq_group' => env('ONBOARDING_FAQ_GROUP_ORGANIZATION'),
        ],

        'kyc' => [
            'enabled' => env('ONBOARDING_REQUIRE_KYC', false),
            'required' => false,
            'documents' => [
                'id' => ['types' => ['passport', 'drivers_license', 'national_id']],
                'proof_of_address' => ['required' => true],
                'selfie' => ['liveness_check' => true],
            ],
            'providers' => [
                'primary' => env('KYC_PROVIDER', 'onfido'),
                'fallback' => env('KYC_FALLBACK_PROVIDER'),
            ],
        ],

        'billing_address' => [
            'enabled' => env('ONBOARDING_COLLECT_BILLING_ADDRESS', false),
            'required' => env('ONBOARDING_REQUIRE_BILLING_ADDRESS', false),
            'same_as_shipping' => true,
            'fields' => [
                'street' => ['required' => true],
                'city' => ['required' => true],
                'state' => ['required' => false],
                'postal_code' => ['required' => true],
                'country' => ['required' => true],
                'company' => ['required' => false],
                'vat_id' => ['required' => false],
            ],
        ],

        'payment_method' => [
            'enabled' => env('ONBOARDING_COLLECT_PAYMENT_METHOD', false),
            'required' => !env('APP_HAS_FREE_TIER', true),
            'providers' => [
                'stripe' => env('CASHIER_PAYMENT_PROVIDER') === 'stripe',
                'paddle' => env('CASHIER_PAYMENT_PROVIDER') === 'paddle',
            ],
            'three_d_secure' => true,
        ],

        'subscription_plan' => [
            'enabled' => env('ONBOARDING_SHOW_PLAN_SELECTION', true),
            'required' => !env('APP_HAS_FREE_TIER', true),
            'show_comparison_table' => true,
            'highlight_recommended' => true,
            'trial_enabled' => env('APP_TRIAL_DAYS', 0) > 0,
            'trial_days' => env('APP_TRIAL_DAYS', 0),
        ],

        'newsletter' => [
            'enabled' => 'auto-detect', // Will check for laravel-mailcoach package
            'required' => false,
            'lists' => env('ONBOARDING_NEWSLETTER_LISTS', []),
            'double_opt_in_gdpr' => true,
            'auto_subscribe_b2b' => env('ONBOARDING_B2B_AUTO_SUBSCRIBE', false),
        ],

        'confirmation' => [
            'enabled' => env('ONBOARDING_SHOW_CONFIRMATION_STEP', false),
            'allow_edit' => true,
            'sections' => ['personal', 'organization', 'billing', 'subscription'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Steps
    |--------------------------------------------------------------------------
    |
    | Register custom onboarding steps that will be dynamically loaded.
    | Each step should define its position, condition, and configuration.
    |
    */
    'custom_steps' => [
        // Example custom step configuration:
        // 'xero_setup' => [
        //     'name' => 'Connect Xero Account',
        //     'view' => 'onboarding.steps.xero',
        //     'controller' => 'App\\Http\\Controllers\\XeroOnboardingController',
        //     'position' => 'after:organization',
        //     'required' => true,
        //     'condition' => 'user.hasRole("admin") && hasPackage("laravel-xero")',
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Third-Party Integrations
    |--------------------------------------------------------------------------
    |
    | Define required third-party service integrations that users must
    | connect during onboarding.
    |
    */
    'required_integrations' => env('ONBOARDING_REQUIRED_INTEGRATIONS', []),

    /*
    |--------------------------------------------------------------------------
    | Progress Tracking
    |--------------------------------------------------------------------------
    |
    | Configure how onboarding progress is tracked and stored.
    |
    */
    'progress' => [
        'storage' => 'session', // 'session' or 'database'
        'fallback' => 'database',
        'session_key' => 'onboarding_progress',
        'expiry_hours' => 48,
        'cleanup_expired' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Business Context
    |--------------------------------------------------------------------------
    |
    | Define the business type and related consent requirements.
    |
    */
    'business' => [
        'type' => env('ONBOARDING_BUSINESS_TYPE', 'b2c'), // 'b2b' or 'b2c'
        'auto_detect' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | GDPR and Consent Management
    |--------------------------------------------------------------------------
    |
    | Configure GDPR compliance and consent management settings.
    |
    */
    'consent' => [
        'gdpr_countries' => [
            'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR',
            'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL',
            'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE', 'GB', 'CH', 'NO',
            'IS', 'LI',
        ],
        'b2b_auto_subscribe_countries' => ['US', 'CA', 'AU', 'NZ'],
        'b2c_explicit_consent_everywhere' => true,
        'log_consent' => true,
        'consent_version' => env('ONBOARDING_CONSENT_VERSION', '1.0'),
        'retention_period' => '3 years',
    ],

    /*
    |--------------------------------------------------------------------------
    | FAQ Integration
    |--------------------------------------------------------------------------
    |
    | Configure FAQ groups to display on each onboarding step.
    |
    */
    'faq_groups' => env('ONBOARDING_FAQ_GROUPS', [
        'personal' => null,
        'organization' => null,
        'billing' => null,
        'subscription' => null,
        'confirmation' => null,
    ]),

    /*
    |--------------------------------------------------------------------------
    | Redirects
    |--------------------------------------------------------------------------
    |
    | Configure where users are redirected at various stages.
    |
    */
    'redirects' => [
        'after_completion' => '/dashboard',
        'after_skip' => '/dashboard',
        'login_required' => '/login',
        'already_completed' => '/dashboard',
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Messages
    |--------------------------------------------------------------------------
    |
    | Customize validation messages for onboarding forms.
    |
    */
    'messages' => [
        'required' => 'This field is required to continue.',
        'email' => 'Please enter a valid email address.',
        'phone' => 'Please enter a valid phone number.',
        'country' => 'Please select your country.',
        'organization_exists' => 'An organization with this name already exists.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Package Detection
    |--------------------------------------------------------------------------
    |
    | Configure package detection for auto-enabling features.
    | Uses class_exists() to check for installed packages.
    |
    */
    'package_detection' => [
        'laravel-organizations' => 'Convertain\\Organizations\\ServiceProvider',
        'laravel-world-data' => 'Convertain\\WorldData\\ServiceProvider',
        'laravel-auth-extended' => 'Convertain\\AuthExtended\\ServiceProvider',
        'laravel-geolocation' => 'Convertain\\Geolocation\\ServiceProvider',
        'laravel-mailcoach' => 'Convertain\\Mailcoach\\ServiceProvider',
        'laravel-gdpr' => 'Convertain\\GDPR\\ServiceProvider',
        'laravel-iubenda' => 'Convertain\\Iubenda\\ServiceProvider',
        'laravel-checkout' => 'Convertain\\Checkout\\ServiceProvider',
        'laravel-subscriptions' => 'Convertain\\Subscriptions\\ServiceProvider',
        'laravel-faq' => 'Convertain\\FAQ\\ServiceProvider',
        'laravel-template' => 'Convertain\\Template\\ServiceProvider',
        'laravel-twilio' => 'Convertain\\Twilio\\ServiceProvider',
        'laravel-kyc' => 'Convertain\\KYC\\ServiceProvider',
        'laravel-xero' => 'Convertain\\Xero\\ServiceProvider',
        'cashier-stripe' => 'Laravel\\Cashier\\CashierServiceProvider',
        'cashier-paddle' => 'Laravel\\Paddle\\CashierServiceProvider',
        'socialite' => 'Laravel\\Socialite\\SocialiteServiceProvider',
    ],
];