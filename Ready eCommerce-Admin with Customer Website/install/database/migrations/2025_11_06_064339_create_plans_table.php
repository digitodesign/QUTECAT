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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Starter, Growth, Enterprise
            $table->string('slug')->unique(); // starter, growth, enterprise
            $table->text('description')->nullable();

            // Pricing
            $table->decimal('price', 10, 2); // Monthly price
            $table->string('currency', 3)->default('USD');
            $table->enum('billing_period', ['monthly', 'yearly'])->default('monthly');
            $table->decimal('yearly_price', 10, 2)->nullable(); // Discounted yearly price

            // Limits
            $table->integer('products_limit')->nullable(); // null = unlimited
            $table->integer('orders_per_month')->nullable(); // null = unlimited
            $table->bigInteger('storage_limit_mb')->default(1024); // 1GB default
            $table->integer('team_members_limit')->default(1);

            // Features (JSON for flexibility)
            $table->json('features')->nullable(); // ["custom_domain", "priority_support", "api_access"]

            // Feature flags
            $table->boolean('custom_domain')->default(false);
            $table->boolean('remove_branding')->default(false);
            $table->boolean('priority_support')->default(false);
            $table->boolean('api_access')->default(false);
            $table->boolean('advanced_analytics')->default(false);
            $table->boolean('multi_currency')->default(false);

            // Trial
            $table->integer('trial_days')->default(14);

            // Visibility
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->integer('sort_order')->default(0);

            // Stripe integration
            $table->string('stripe_product_id')->nullable();
            $table->string('stripe_price_id')->nullable();
            $table->string('stripe_yearly_price_id')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
