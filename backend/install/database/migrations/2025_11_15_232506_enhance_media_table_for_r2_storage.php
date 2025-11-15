<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Enhances media table for Cloudflare R2 storage integration
     * with multi-vendor support and image optimization
     */
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table) {
            // Multi-vendor scoping
            $table->foreignId('shop_id')->nullable()->after('id')->constrained('shops')->nullOnDelete();
            $table->index('shop_id');

            // Storage configuration
            $table->string('disk', 50)->default('r2')->after('extention')->comment('Storage disk: r2, r2-private, public');
            $table->unsignedBigInteger('size')->nullable()->after('disk')->comment('File size in bytes');
            $table->string('mime_type', 100)->nullable()->after('size');

            // Image optimization fields
            $table->string('optimized_src', 500)->nullable()->after('src')->comment('WebP optimized version path');
            $table->json('responsive_sizes')->nullable()->after('optimized_src')->comment('Thumbnail, small, medium, large URLs');
            $table->boolean('is_optimized')->default(false)->after('responsive_sizes');
            $table->index('is_optimized');

            // Image dimensions
            $table->unsignedInteger('width')->nullable()->after('is_optimized')->comment('Original image width');
            $table->unsignedInteger('height')->nullable()->after('width')->comment('Original image height');

            // Processing status
            $table->string('processing_status', 50)->default('pending')->after('height')->comment('pending, processing, completed, failed');
            $table->timestamp('processed_at')->nullable()->after('processing_status');
            $table->index('processing_status');

            // Add index on disk for faster queries
            $table->index('disk');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropForeign(['shop_id']);
            $table->dropIndex(['shop_id']);
            $table->dropIndex(['is_optimized']);
            $table->dropIndex(['processing_status']);
            $table->dropIndex(['disk']);

            $table->dropColumn([
                'shop_id',
                'disk',
                'size',
                'mime_type',
                'optimized_src',
                'responsive_sizes',
                'is_optimized',
                'width',
                'height',
                'processing_status',
                'processed_at',
            ]);
        });
    }
};
