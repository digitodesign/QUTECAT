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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id'); // UUID from tenants table
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreignId('plan_id')->constrained('plans')->onDelete('restrict');

            // Subscription details
            $table->enum('status', [
                'trialing',
                'active',
                'past_due',
                'canceled',
                'unpaid',
                'paused',
                'incomplete',
                'incomplete_expired'
            ])->default('trialing');

            // Dates
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamp('ends_at')->nullable(); // Grace period end

            // Billing
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->enum('billing_period', ['monthly', 'yearly'])->default('monthly');

            // Payment gateway
            $table->string('stripe_subscription_id')->nullable()->unique();
            $table->string('stripe_customer_id')->nullable();
            $table->string('stripe_status')->nullable();

            // Metadata
            $table->json('metadata')->nullable(); // Custom data

            $table->timestamps();

            // Indexes
            $table->index(['tenant_id', 'status']);
            $table->index('stripe_subscription_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
