<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Link tenants table to shops table for hybrid marketplace model.
     * In our architecture:
     * - Free vendors: No tenant record (marketplace only)
     * - Premium vendors: Have tenant record (marketplace + subdomain)
     */
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // Link to shop instead of creating separate business data
            $table->foreignId('shop_id')
                ->nullable()
                ->after('id')
                ->constrained('shops')
                ->onDelete('cascade')
                ->comment('Link to shop (null for non-shop tenants)');

            // Premium vendor specific fields
            $table->string('subdomain', 63)
                ->nullable()
                ->unique()
                ->after('shop_id')
                ->comment('Premium vendor subdomain (e.g., "johns-shop")');

            $table->enum('tier', ['free', 'starter', 'growth', 'enterprise'])
                ->default('free')
                ->after('subdomain')
                ->comment('Subscription tier');

            $table->timestamp('premium_since')
                ->nullable()
                ->after('tier')
                ->comment('When vendor upgraded to premium');

            $table->timestamp('premium_expires_at')
                ->nullable()
                ->after('premium_since')
                ->comment('Premium subscription expiration');

            // Remove fields we don't need (they exist in shops table)
            // Note: We keep these commented for reference but won't drop
            // $table->dropColumn(['shop_name', 'owner_email', 'stripe_customer_id']);
        });

        // Add index for performance
        Schema::table('tenants', function (Blueprint $table) {
            $table->index('shop_id', 'tenants_shop_id_index');
            $table->index('subdomain', 'tenants_subdomain_index');
            $table->index('tier', 'tenants_tier_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex('tenants_shop_id_index');
            $table->dropIndex('tenants_subdomain_index');
            $table->dropIndex('tenants_tier_index');

            // Drop foreign key and columns
            $table->dropForeign(['shop_id']);
            $table->dropColumn([
                'shop_id',
                'subdomain',
                'tier',
                'premium_since',
                'premium_expires_at',
            ]);
        });
    }
};
