<?php

namespace App\Repositories;

use Abedin\Maker\Repositories\Repository;
use App\Jobs\ProcessImageOptimization;
use App\Jobs\ProcessVideoThumbnail;
use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaRepository extends Repository
{
    /**
     * base method
     *
     * @method model()
     */
    public static function model()
    {
        return Media::class;
    }

    /**
     * Store a file with Cloudflare R2 storage integration
     *
     * @param  UploadedFile  $file  The file to store
     * @param  string  $path  The path to store the file (e.g., 'products', 'shops/logos')
     * @param  string|null  $type  The type of the file (image, video, pdf, etc.)
     * @param  int|null  $shopId  Shop ID for multi-vendor scoping
     * @param  string  $visibility  'public' or 'private'
     * @return Media The created media object
     */
    public static function storeByRequest(
        UploadedFile $file,
        string $path,
        ?string $type = null,
        ?int $shopId = null,
        string $visibility = 'public'
    ): Media {
        // Determine disk based on visibility
        $disk = $visibility === 'private' ? 'r2-private' : config('filesystems.default', 'r2');

        // Get file info
        $extension = $file->extension();
        $mimeType = $file->getMimeType();
        $size = $file->getSize();

        // Auto-detect type if not provided
        if (!$type) {
            $type = self::detectFileType($extension, $mimeType);
        }

        // Get shop ID from authenticated user if not provided
        if (!$shopId && Auth::check()) {
            $user = Auth::user();
            $shopId = $user->shop_id ?? $user->shop?->id;
        }

        // Generate unique filename with UUID
        $filename = Str::uuid() . '.' . $extension;

        // Build full path with shop isolation
        if ($shopId) {
            $fullPath = trim($path, '/') . '/shop-' . $shopId . '/' . $filename;
        } else {
            $fullPath = trim($path, '/') . '/' . $filename;
        }

        // Upload to R2
        try {
            $uploadedPath = Storage::disk($disk)->putFileAs(
                dirname($fullPath),
                $file,
                basename($fullPath),
                $visibility
            );

            // Get image dimensions if it's an image
            $width = null;
            $height = null;

            if ($type === 'image' && in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                try {
                    $imageInfo = getimagesize($file->getRealPath());
                    $width = $imageInfo[0] ?? null;
                    $height = $imageInfo[1] ?? null;
                } catch (\Exception $e) {
                    Log::warning('Failed to get image dimensions', [
                        'file' => $file->getClientOriginalName(),
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Create media record
            $media = self::create([
                'shop_id' => $shopId,
                'type' => $type,
                'name' => $file->getClientOriginalName(),
                'original_name' => $file->getClientOriginalName(),
                'src' => $uploadedPath,
                'extention' => $extension, // Keep legacy column name
                'disk' => $disk,
                'size' => $size,
                'mime_type' => $mimeType,
                'width' => $width,
                'height' => $height,
                'is_optimized' => false,
                'processing_status' => 'pending',
            ]);

            // Queue image optimization for supported formats
            if ($type === 'image' && in_array($extension, ['jpg', 'jpeg', 'png'])) {
                ProcessImageOptimization::dispatch($media)
                    ->delay(now()->addSeconds(5)); // Small delay to ensure DB commit
            }

            // Queue video thumbnail generation
            if ($type === 'video') {
                ProcessVideoThumbnail::dispatch($media)
                    ->delay(now()->addSeconds(5));
            }

            Log::info('Media file uploaded to R2', [
                'media_id' => $media->id,
                'shop_id' => $shopId,
                'type' => $type,
                'size' => $size,
                'path' => $uploadedPath,
            ]);

            return $media;

        } catch (\Exception $e) {
            Log::error('Failed to upload media to R2', [
                'path' => $fullPath,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Update a media file
     *
     * @param  UploadedFile  $file  The file to be uploaded
     * @param  string  $path  The path for the file
     * @param  ?string  $type  The type of the file
     * @param  Media  $media  The media object to be updated
     * @return Media The updated media object
     */
    public static function updateByRequest(
        UploadedFile $file,
        string $path,
        ?string $type,
        Media $media
    ): Media {
        // Delete old file from R2
        if ($media->src) {
            try {
                Storage::disk($media->disk)->delete($media->src);

                // Delete optimized versions
                if ($media->optimized_src) {
                    Storage::disk($media->disk)->delete($media->optimized_src);
                }
            } catch (\Exception $e) {
                Log::warning('Failed to delete old media file', [
                    'media_id' => $media->id,
                    'path' => $media->src,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Upload new file
        $disk = $media->disk ?? config('filesystems.default', 'r2');
        $extension = $file->extension();
        $mimeType = $file->getMimeType();
        $size = $file->getSize();

        if (!$type) {
            $type = self::detectFileType($extension, $mimeType);
        }

        // Generate new filename
        $filename = Str::uuid() . '.' . $extension;

        // Build path with shop isolation
        if ($media->shop_id) {
            $fullPath = trim($path, '/') . '/shop-' . $media->shop_id . '/' . $filename;
        } else {
            $fullPath = trim($path, '/') . '/' . $filename;
        }

        // Upload to R2
        $uploadedPath = Storage::disk($disk)->putFileAs(
            dirname($fullPath),
            $file,
            basename($fullPath),
            'public'
        );

        // Get dimensions for images
        $width = null;
        $height = null;

        if ($type === 'image' && in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            try {
                $imageInfo = getimagesize($file->getRealPath());
                $width = $imageInfo[0] ?? null;
                $height = $imageInfo[1] ?? null;
            } catch (\Exception $e) {
                // Ignore dimension errors
            }
        }

        // Update media record
        $media->update([
            'type' => $type,
            'name' => $file->getClientOriginalName(),
            'original_name' => $file->getClientOriginalName(),
            'src' => $uploadedPath,
            'extention' => $extension,
            'disk' => $disk,
            'size' => $size,
            'mime_type' => $mimeType,
            'width' => $width,
            'height' => $height,
            'is_optimized' => false,
            'optimized_src' => null,
            'responsive_sizes' => null,
            'processing_status' => 'pending',
        ]);

        // Queue optimization
        if ($type === 'image' && in_array($extension, ['jpg', 'jpeg', 'png'])) {
            ProcessImageOptimization::dispatch($media)
                ->delay(now()->addSeconds(5));
        }

        if ($type === 'video') {
            ProcessVideoThumbnail::dispatch($media)
                ->delay(now()->addSeconds(5));
        }

        Log::info('Media file updated', [
            'media_id' => $media->id,
            'shop_id' => $media->shop_id,
            'new_path' => $uploadedPath,
        ]);

        return $media;
    }

    /**
     * Detect file type based on extension and MIME type
     *
     * @param string $extension File extension
     * @param string $mimeType MIME type
     * @return string File type
     */
    private static function detectFileType(string $extension, string $mimeType): string
    {
        // Images
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'])) {
            return 'image';
        }

        // Videos
        if (in_array($extension, ['mp4', 'webm', 'ogg', 'mov', 'avi'])) {
            return 'video';
        }

        // Documents
        if (in_array($extension, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'])) {
            return 'document';
        }

        // Archives
        if (in_array($extension, ['zip', 'rar', '7z', 'tar', 'gz'])) {
            return 'archive';
        }

        // Default to extension or generic file
        return $extension ?: 'file';
    }

    /**
     * Calculate total storage used by a shop (in bytes)
     *
     * @param int $shopId Shop ID
     * @return int Total storage in bytes
     */
    public static function getShopStorageUsage(int $shopId): int
    {
        return Media::where('shop_id', $shopId)->sum('size') ?? 0;
    }

    /**
     * Check if shop has exceeded storage quota
     *
     * @param int $shopId Shop ID
     * @param int $quotaBytes Storage quota in bytes
     * @return bool True if quota exceeded
     */
    public static function hasExceededQuota(int $shopId, int $quotaBytes): bool
    {
        $used = self::getShopStorageUsage($shopId);
        return $used >= $quotaBytes;
    }
}
