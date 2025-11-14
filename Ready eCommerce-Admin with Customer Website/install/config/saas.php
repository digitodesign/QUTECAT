<?php

/**
 * QuteCart SaaS Configuration
 *
 * Centralized configuration for subscription plans, limits, and features.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Subscription Plans
    |--------------------------------------------------------------------------
    |
    | Define all available subscription tiers with their limits and features.
    | These should match the plans in your database (plans table).
    |
    */

    'plans' => [
        'free' => [
            'name' => 'Free Marketplace Vendor',
            'slug' => 'free',
            'price' => 0,
            'billing_cycle' => null,
            'trial_days' => 0,

            // Limits
            'products_limit' => env('FREE_TIER_PRODUCTS_LIMIT', 25),
            'orders_per_month' => env('FREE_TIER_ORDERS_LIMIT', 100),
            'storage_mb' => env('FREE_TIER_STORAGE_LIMIT_MB', 500),
            'staff_accounts' => 0,

            // Features
            'features' => [
                'marketplace_presence' => true,
                'premium_subdomain' => false,
                'custom_branding' => false,
                'priority_support' => false,
                'advanced_analytics' => false,
                'api_access' => false,
                'multi_location' => false,
                'remove_branding' => false,
            ],

            'description' => 'Perfect for getting started on the marketplace',
            'highlight' => false,
        ],

        'starter' => [
            'name' => 'Starter',
            'slug' => 'starter',
            'price' => 29.00,
            'billing_cycle' => 'monthly',
            'trial_days' => 14,

            // Limits
            'products_limit' => 100,
            'orders_per_month' => 500,
            'storage_mb' => 5120, // 5GB
            'staff_accounts' => 2,

            // Features
            'features' => [
                'marketplace_presence' => true,
                'premium_subdomain' => true,
                'custom_branding' => false,
                'priority_support' => false,
                'advanced_analytics' => false,
                'api_access' => false,
                'multi_location' => false,
                'remove_branding' => false,
            ],

            'description' => 'Ideal for small businesses ready to grow',
            'highlight' => false,
        ],

        'growth' => [
            'name' => 'Growth',
            'slug' => 'growth',
            'price' => 99.00,
            'billing_cycle' => 'monthly',
            'trial_days' => 14,

            // Limits
            'products_limit' => 1000,
            'orders_per_month' => null, // unlimited
            'storage_mb' => 51200, // 50GB
            'staff_accounts' => 10,

            // Features
            'features' => [
                'marketplace_presence' => true,
                'premium_subdomain' => true,
                'custom_branding' => true,
                'priority_support' => true,
                'advanced_analytics' => true,
                'api_access' => true,
                'multi_location' => false,
                'remove_branding' => true,
            ],

            'description' => 'For established businesses scaling up',
            'highlight' => true, // Most popular
        ],

        'enterprise' => [
            'name' => 'Enterprise',
            'slug' => 'enterprise',
            'price' => 299.00,
            'billing_cycle' => 'monthly',
            'trial_days' => 30, // Longer trial for enterprise

            // Limits
            'products_limit' => null, // unlimited
            'orders_per_month' => null, // unlimited
            'storage_mb' => null, // unlimited
            'staff_accounts' => null, // unlimited

            // Features
            'features' => [
                'marketplace_presence' => true,
                'premium_subdomain' => true,
                'custom_branding' => true,
                'priority_support' => true,
                'advanced_analytics' => true,
                'api_access' => true,
                'multi_location' => true,
                'remove_branding' => true,
                'dedicated_support' => true,
                'custom_integrations' => true,
            ],

            'description' => 'Everything you need to run a large operation',
            'highlight' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Stripe Configuration
    |--------------------------------------------------------------------------
    |
    | Stripe API credentials for subscription billing.
    |
    */

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'currency' => env('STRIPE_CURRENCY', 'usd'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Limits (Free Tier)
    |--------------------------------------------------------------------------
    |
    | Default resource limits applied to new shops before they subscribe.
    |
    */

    'default_limits' => [
        'products' => env('FREE_TIER_PRODUCTS_LIMIT', 25),
        'orders_per_month' => env('FREE_TIER_ORDERS_LIMIT', 100),
        'storage_mb' => env('FREE_TIER_STORAGE_LIMIT_MB', 500),
        'staff_accounts' => 0,
    ],

    /*
    |--------------------------------------------------------------------------
    | Usage Tracking
    |--------------------------------------------------------------------------
    |
    | Configuration for tracking vendor resource usage.
    |
    */

    'usage_tracking' => [
        // Enable usage tracking
        'enabled' => true,

        // When to reset monthly usage counters
        'reset_on' => 'first_day_of_month',

        // Warning thresholds (percentage)
        'warning_thresholds' => [
            'products' => 80, // Warn at 80% of limit
            'orders' => 90,   // Warn at 90% of limit
            'storage' => 85,  // Warn at 85% of limit
        ],

        // Send email notifications
        'notifications' => [
            'approaching_limit' => true,
            'limit_exceeded' => true,
            'monthly_report' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Trial Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for trial periods on paid plans.
    |
    */

    'trial' => [
        // Enable trial periods
        'enabled' => true,

        // Default trial length (days)
        'days' => 14,

        // Default trial plan
        'plan' => 'starter',

        // Require payment method during trial
        'require_payment_method' => true,

        // Auto-cancel if no payment method added
        'auto_cancel_without_payment' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Upgrade/Downgrade Behavior
    |--------------------------------------------------------------------------
    |
    | How to handle plan changes.
    |
    */

    'plan_changes' => [
        // Upgrade: immediate or end of period
        'upgrade' => 'immediate',

        // Downgrade: immediate or end of period
        'downgrade' => 'end_of_period',

        // Prorate charges on upgrade
        'prorate_upgrade' => true,

        // Prorate credits on downgrade
        'prorate_downgrade' => true,

        // Grace period after failed payment (days)
        'grace_period_days' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Enable/disable specific SaaS features.
    |
    */

    'features' => [
        // Allow subdomain customization
        'subdomains_enabled' => true,

        // Allow custom branding
        'custom_branding_enabled' => true,

        // Advanced analytics
        'analytics_enabled' => true,

        // API access for vendors
        'api_access_enabled' => true,

        // Multi-location support
        'multi_location_enabled' => true,

        // Self-service upgrades
        'self_service_upgrades' => true,

        // Self-service cancellations
        'self_service_cancellations' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Limits Enforcement
    |--------------------------------------------------------------------------
    |
    | How strictly to enforce limits.
    |
    */

    'enforcement' => [
        // Block actions when limit exceeded
        'hard_limits' => true,

        // Or allow with warnings
        'soft_limits' => false,

        // Grace buffer (allow X% over limit temporarily)
        'grace_buffer_percent' => 5,

        // Grace period duration (hours)
        'grace_period_hours' => 24,
    ],

    /*
    |--------------------------------------------------------------------------
    | Pricing Display
    |--------------------------------------------------------------------------
    |
    | Settings for displaying pricing to customers.
    |
    */

    'pricing' => [
        // Show annual pricing (if available)
        'show_annual' => true,

        // Annual discount percentage
        'annual_discount' => 20,

        // Currency symbol
        'currency_symbol' => '$',

        // Currency position (before/after)
        'currency_position' => 'before',

        // Show "most popular" badge
        'show_popular_badge' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Subdomain Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for premium vendor subdomains.
    |
    */

    'subdomains' => [
        // Main domain
        'domain' => env('APP_DOMAIN', 'qutekart.com'),

        // Allow custom subdomains
        'custom_subdomains' => true,

        // Minimum subdomain length
        'min_length' => 3,

        // Maximum subdomain length
        'max_length' => 32,

        // Reserved subdomains (can't be used by vendors)
        'reserved' => [
            'www',
            'api',
            'admin',
            'app',
            'mail',
            'ftp',
            'smtp',
            'support',
            'help',
            'blog',
            'shop',
            'store',
            'qutekart',
            'qutecat',
        ],

        // Allowed characters (regex)
        'allowed_pattern' => '/^[a-z0-9-]+$/',
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Settings
    |--------------------------------------------------------------------------
    |
    | File storage limits and policies.
    |
    */

    'storage' => [
        // Maximum file upload size (MB)
        'max_file_size_mb' => 10,

        // Allowed file types
        'allowed_types' => [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'application/pdf',
        ],

        // Calculate storage usage
        'track_usage' => true,

        // Storage calculation frequency
        'calculate_frequency' => 'daily', // daily, weekly
    ],

];
