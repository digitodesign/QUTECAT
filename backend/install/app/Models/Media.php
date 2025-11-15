<?php

namespace App\Models;

use App\Services\Storage\PresignedUrlService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'responsive_sizes' => 'array',
        'is_optimized' => 'boolean',
        'processed_at' => 'datetime',
    ];

    /**
     * Relationships
     */

    /**
     * Get the shop that owns this media (multi-vendor scoping)
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * Accessors & Mutators
     */

    /**
     * Get public URL for media file
     *
     * Uses optimized WebP version if available, otherwise original
     * Caches URLs for 1 hour for performance
     */
    public function srcUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                return Cache::remember("media.{$this->id}.url", 3600, function () {
                    // Use optimized version if available
                    $path = $this->optimized_src ?? $this->src;

                    if (!$path) {
                        return asset('default/default.jpg');
                    }

                    $disk = $this->disk ?? config('filesystems.default');

                    // For R2 storage (public)
                    if ($disk === 'r2') {
                        $r2Service = app(PresignedUrlService::class);
                        return $r2Service->getPublicUrl($path);
                    }

                    // For R2 private storage (requires presigned URL)
                    if ($disk === 'r2-private') {
                        return $this->getPresignedDownloadUrl(60);
                    }

                    // Fallback to local storage
                    if (Storage::disk($disk)->exists($path)) {
                        return Storage::disk($disk)->url($path);
                    }

                    return asset('default/default.jpg');
                });
            },
        );
    }

    /**
     * Get responsive image URL for a specific size
     *
     * @param string $size Size name: thumbnail, small, medium, large
     * @return string Image URL
     */
    public function getResponsiveUrl(string $size = 'medium'): string
    {
        // Return responsive size if available
        if ($this->responsive_sizes && isset($this->responsive_sizes[$size])) {
            return $this->responsive_sizes[$size];
        }

        // Fallback to main URL
        return $this->srcUrl;
    }

    /**
     * Generate presigned download URL (for private files)
     *
     * @param int $expiresIn Expiration in minutes (default: 60)
     * @param string|null $filename Force download with specific filename
     * @return string Presigned download URL
     */
    public function getPresignedDownloadUrl(int $expiresIn = 60, ?string $filename = null): string
    {
        if (in_array($this->disk, ['r2', 'r2-private'])) {
            $r2Service = app(PresignedUrlService::class);
            return $r2Service->generateDownloadUrl($this->src, $expiresIn, $filename);
        }

        // Fallback to regular URL for non-R2 storage
        return $this->srcUrl;
    }

    /**
     * Check if image is optimized
     */
    public function isOptimized(): bool
    {
        return $this->is_optimized && $this->optimized_src !== null;
    }

    /**
     * Get file size in human-readable format
     */
    public function getFileSizeAttribute(): string
    {
        if (!$this->size) {
            return 'Unknown';
        }

        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Scopes
     */

    /**
     * Scope to filter by shop (multi-vendor isolation)
     */
    public function scopeForShop($query, int $shopId)
    {
        return $query->where('shop_id', $shopId);
    }

    /**
     * Scope to get only optimized media
     */
    public function scopeOptimized($query)
    {
        return $query->where('is_optimized', true);
    }

    /**
     * Scope to get pending optimization
     */
    public function scopePendingOptimization($query)
    {
        return $query->where('is_optimized', false)
            ->where('type', 'image')
            ->where('processing_status', 'pending');
    }

    /**
     * Scope to get failed optimization
     */
    public function scopeFailedOptimization($query)
    {
        return $query->where('processing_status', 'failed');
    }

    /**
     * Events
     */

    /**
     * Boot the model
     */
    protected static function booted(): void
    {
        // Clear cache when media is updated
        static::updated(function ($media) {
            Cache::forget("media.{$media->id}.url");
        });

        // Clear cache when media is deleted
        static::deleted(function ($media) {
            Cache::forget("media.{$media->id}.url");

            // Delete file from R2
            if (in_array($media->disk, ['r2', 'r2-private'])) {
                $r2Service = app(PresignedUrlService::class);

                // Delete original
                if ($media->src) {
                    $r2Service->delete($media->src);
                }

                // Delete optimized version
                if ($media->optimized_src) {
                    $r2Service->delete($media->optimized_src);
                }

                // Delete responsive sizes
                if ($media->responsive_sizes) {
                    foreach ($media->responsive_sizes as $size => $url) {
                        // Extract path from URL
                        $path = parse_url($url, PHP_URL_PATH);
                        if ($path) {
                            $r2Service->delete(ltrim($path, '/'));
                        }
                    }
                }
            }
        });
    }
}
