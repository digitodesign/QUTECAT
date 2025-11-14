<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add premium vendor features to existing shops table.
     * This enhances the existing shop functionality without breaking it.
     */
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            // Subscription Management
            $table->foreignId('current_plan_id')
                ->nullable()
                ->after('id')
                ->constrained('plans')
                ->nullOnDelete()
                ->comment('Current subscription plan (null = free tier)');

            $table->enum('subscription_status', [
                'active',
                'trialing',
                'past_due',
                'canceled',
                'incomplete',
                'incomplete_expired',
                'unpaid',
            ])
                ->default('active')
                ->after('current_plan_id')
                ->comment('Stripe subscription status');

            $table->string('stripe_customer_id', 100)
                ->nullable()
                ->unique()
                ->after('subscription_status')
                ->comment('Stripe customer ID for billing');

            $table->string('stripe_subscription_id', 100)
                ->nullable()
                ->unique()
                ->after('stripe_customer_id')
                ->comment('Stripe subscription ID');

            // Premium Features
            $table->boolean('has_premium_subdomain')
                ->default(false)
                ->after('stripe_subscription_id')
                ->comment('Whether vendor has premium subdomain');

            $table->boolean('custom_branding_enabled')
                ->default(false)
                ->after('has_premium_subdomain')
                ->comment('Can customize colors, logo, etc.');

            $table->boolean('priority_support')
                ->default(false)
                ->after('custom_branding_enabled')
                ->comment('Has priority customer support');

            $table->boolean('analytics_enabled')
                ->default(false)
                ->after('priority_support')
                ->comment('Has access to advanced analytics');

            // Usage Limits & Tracking
            $table->integer('products_limit')
                ->default(25)
                ->after('analytics_enabled')
                ->comment('Max products allowed (free tier: 25)');

            $table->integer('orders_per_month_limit')
                ->default(100)
                ->after('products_limit')
                ->comment('Max orders per month (free tier: 100)');

            $table->bigInteger('storage_limit_mb')
                ->default(500)
                ->after('orders_per_month_limit')
                ->comment('Storage limit in MB (free tier: 500)');

            $table->integer('products_count')
                ->default(0)
                ->after('storage_limit_mb')
                ->comment('Current product count (cached)');

            $table->integer('orders_this_month')
                ->default(0)
                ->after('products_count')
                ->comment('Orders this month (reset monthly)');

            $table->bigInteger('storage_used_mb')
                ->default(0)
                ->after('orders_this_month')
                ->comment('Storage used in MB (cached)');

            // Billing Dates
            $table->timestamp('trial_ends_at')
                ->nullable()
                ->after('storage_used_mb')
                ->comment('Trial period end date');

            $table->timestamp('subscription_started_at')
                ->nullable()
                ->after('trial_ends_at')
                ->comment('When subscription started');

            $table->timestamp('subscription_ends_at')
                ->nullable()
                ->after('subscription_started_at')
                ->comment('When subscription ends/renews');

            $table->timestamp('last_usage_reset_at')
                ->nullable()
                ->after('subscription_ends_at')
                ->comment('Last time monthly usage was reset');

            // Preferences
            $table->json('premium_settings')
                ->nullable()
                ->after('last_usage_reset_at')
                ->comment('Premium vendor settings (branding, colors, etc.)');
        });

        // Add indexes for performance
        Schema::table('shops', function (Blueprint $table) {
            $table->index('current_plan_id', 'shops_current_plan_id_index');
            $table->index('subscription_status', 'shops_subscription_status_index');
            $table->index('stripe_customer_id', 'shops_stripe_customer_id_index');
            $table->index('has_premium_subdomain', 'shops_has_premium_subdomain_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex('shops_current_plan_id_index');
            $table->dropIndex('shops_subscription_status_index');
            $table->dropIndex('shops_stripe_customer_id_index');
            $table->dropIndex('shops_has_premium_subdomain_index');

            // Drop foreign key
            $table->dropForeign(['current_plan_id']);

            // Drop all added columns
            $table->dropColumn([
                'current_plan_id',
                'subscription_status',
                'stripe_customer_id',
                'stripe_subscription_id',
                'has_premium_subdomain',
                'custom_branding_enabled',
                'priority_support',
                'analytics_enabled',
                'products_limit',
                'orders_per_month_limit',
                'storage_limit_mb',
                'products_count',
                'orders_this_month',
                'storage_used_mb',
                'trial_ends_at',
                'subscription_started_at',
                'subscription_ends_at',
                'last_usage_reset_at',
                'premium_settings',
            ]);
        });
    }
};
