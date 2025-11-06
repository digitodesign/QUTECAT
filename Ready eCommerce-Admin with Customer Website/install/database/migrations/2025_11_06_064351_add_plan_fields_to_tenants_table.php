<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // Shop/Business information
            $table->string('shop_name')->after('id');
            $table->string('owner_name')->nullable();
            $table->string('owner_email')->unique();
            $table->string('owner_phone')->nullable();

            // Status
            $table->enum('status', ['active', 'suspended', 'trial', 'canceled'])->default('trial');

            // Trial
            $table->timestamp('trial_ends_at')->nullable();

            // Billing
            $table->string('stripe_customer_id')->nullable();
            $table->string('pm_type')->nullable(); // payment method type
            $table->string('pm_last_four', 4)->nullable(); // last 4 digits

            // Metadata
            $table->string('timezone')->default('UTC');
            $table->string('currency', 3)->default('USD');
            $table->string('logo_url')->nullable();
            $table->json('settings')->nullable(); // Custom tenant settings

            // Tracking
            $table->timestamp('last_activity_at')->nullable();
            $table->ipAddress('last_ip')->nullable();

            // Indexes
            $table->index('owner_email');
            $table->index('status');
            $table->index('stripe_customer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropIndex(['owner_email']);
            $table->dropIndex(['status']);
            $table->dropIndex(['stripe_customer_id']);

            $table->dropColumn([
                'shop_name',
                'owner_name',
                'owner_email',
                'owner_phone',
                'status',
                'trial_ends_at',
                'stripe_customer_id',
                'pm_type',
                'pm_last_four',
                'timezone',
                'currency',
                'logo_url',
                'settings',
                'last_activity_at',
                'last_ip',
            ]);
        });
    }
};
