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
        Schema::create('usage_tracking', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');

            // Period tracking
            $table->date('period_start');
            $table->date('period_end');

            // Usage metrics
            $table->integer('products_count')->default(0);
            $table->integer('orders_count')->default(0);
            $table->integer('customers_count')->default(0);
            $table->bigInteger('storage_used_mb')->default(0);
            $table->integer('api_requests_count')->default(0);
            $table->integer('team_members_count')->default(1);

            // Revenue tracking (optional)
            $table->decimal('revenue', 12, 2)->default(0);

            // Metadata
            $table->json('metadata')->nullable();

            $table->timestamps();

            // Indexes
            $table->unique(['tenant_id', 'period_start', 'period_end']);
            $table->index(['tenant_id', 'period_start']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usage_tracking');
    }
};
