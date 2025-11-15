<?php

namespace App\Jobs;

use App\Models\Media;
use App\Services\Storage\PresignedUrlService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Process Image Optimization Job
 *
 * Converts images to WebP format and generates responsive sizes
 * for optimal performance in multi-vendor e-commerce platform
 */
class ProcessImageOptimization implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 3;
    public $backoff = [10, 30, 60]; // Retry delays in seconds

    /**
     * Create a new job instance.
     *
     * @param Media $media The media record to optimize
     * @param array $sizes Responsive sizes to generate
     */
    public function __construct(
        public Media $media,
        public array $sizes = []
    ) {
        // Default responsive sizes for e-commerce
        if (empty($this->sizes)) {
            $this->sizes = [
                'thumbnail' => ['width' => 150, 'height' => 150],
                'small' => ['width' => 300, 'height' => 300],
                'medium' => ['width' => 600, 'height' => 600],
                'large' => ['width' => 1200, 'height' => 1200],
            ];
        }

        // Queue to 'media' queue for dedicated worker
        $this->onQueue('media');
    }

    /**
     * Execute the job.
     */
    public function handle(PresignedUrlService $r2Service): void
    {
        try {
            // Update status to processing
            $this->media->update(['processing_status' => 'processing']);

            $disk = Storage::disk($this->media->disk ?? 'r2');
            $originalPath = $this->media->src;

            // Skip optimization for non-image files
            if (!in_array($this->media->type, ['image', 'thumbnail'])) {
                $this->media->update([
                    'processing_status' => 'completed',
                    'processed_at' => now(),
                ]);
                return;
            }

            // Download original image from R2 to temp storage
            $tempDir = storage_path('app/temp');
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $tempOriginal = $tempDir . '/' . basename($originalPath);
            $content = $disk->get($originalPath);
            file_put_contents($tempOriginal, $content);

            // Get image info
            $imageInfo = getimagesize($tempOriginal);
            $originalWidth = $imageInfo[0];
            $originalHeight = $imageInfo[1];
            $mimeType = $imageInfo['mime'];

            // Load image based on type
            $sourceImage = $this->loadImage($tempOriginal, $mimeType);
            if (!$sourceImage) {
                throw new \Exception('Failed to load image: ' . $originalPath);
            }

            // Optimize and convert to WebP
            $optimizedPath = $this->optimizeToWebP(
                $sourceImage,
                $originalPath,
                $originalWidth,
                $originalHeight,
                $disk
            );

            // Generate responsive sizes
            $responsiveSizes = $this->generateResponsiveSizes(
                $tempOriginal,
                $originalPath,
                $mimeType,
                $disk,
                $r2Service
            );

            // Update media record
            $this->media->update([
                'optimized_src' => $optimizedPath,
                'responsive_sizes' => $responsiveSizes,
                'is_optimized' => true,
                'width' => $originalWidth,
                'height' => $originalHeight,
                'processing_status' => 'completed',
                'processed_at' => now(),
            ]);

            // Cleanup temp files
            if (file_exists($tempOriginal)) {
                unlink($tempOriginal);
            }

            // Clean up any other temp files
            $tempFiles = glob($tempDir . '/' . pathinfo($originalPath, PATHINFO_FILENAME) . '*');
            foreach ($tempFiles as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }

            Log::info('Image optimized successfully', [
                'media_id' => $this->media->id,
                'shop_id' => $this->media->shop_id,
                'original' => $originalPath,
                'optimized' => $optimizedPath,
                'sizes_generated' => count($responsiveSizes),
            ]);

        } catch (\Exception $e) {
            // Update status to failed
            $this->media->update([
                'processing_status' => 'failed',
            ]);

            Log::error('Image optimization failed', [
                'media_id' => $this->media->id,
                'shop_id' => $this->media->shop_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw to trigger job retry
            throw $e;
        }
    }

    /**
     * Load image resource from file
     */
    private function loadImage(string $path, string $mimeType)
    {
        return match ($mimeType) {
            'image/jpeg', 'image/jpg' => imagecreatefromjpeg($path),
            'image/png' => imagecreatefrompng($path),
            'image/gif' => imagecreatefromgif($path),
            'image/webp' => imagecreatefromwebp($path),
            default => false,
        };
    }

    /**
     * Optimize image and convert to WebP
     */
    private function optimizeToWebP($sourceImage, string $originalPath, int $width, int $height, $disk): string
    {
        // Create WebP version
        $webpPath = preg_replace('/\.[^.]+$/', '.webp', $originalPath);

        // Create temp file for WebP
        $tempWebp = storage_path('app/temp/' . basename($webpPath));

        // Convert to WebP with 85% quality (good balance)
        imagewebp($sourceImage, $tempWebp, 85);

        // Upload to R2
        $disk->put($webpPath, file_get_contents($tempWebp));

        // Cleanup
        if (file_exists($tempWebp)) {
            unlink($tempWebp);
        }

        imagedestroy($sourceImage);

        return $webpPath;
    }

    /**
     * Generate responsive image sizes
     */
    private function generateResponsiveSizes(
        string $tempOriginal,
        string $originalPath,
        string $mimeType,
        $disk,
        PresignedUrlService $r2Service
    ): array {
        $generatedSizes = [];

        foreach ($this->sizes as $sizeName => $dimensions) {
            try {
                // Load original image
                $sourceImage = $this->loadImage($tempOriginal, $mimeType);
                if (!$sourceImage) {
                    continue;
                }

                $originalWidth = imagesx($sourceImage);
                $originalHeight = imagesy($sourceImage);

                // Calculate new dimensions maintaining aspect ratio
                $ratio = min(
                    $dimensions['width'] / $originalWidth,
                    $dimensions['height'] / $originalHeight
                );

                $newWidth = (int) ($originalWidth * $ratio);
                $newHeight = (int) ($originalHeight * $ratio);

                // Create resized image
                $resizedImage = imagecreatetruecolor($newWidth, $newHeight);

                // Preserve transparency for PNG/GIF
                if (in_array($mimeType, ['image/png', 'image/gif'])) {
                    imagealphablending($resizedImage, false);
                    imagesavealpha($resizedImage, true);
                    $transparent = imagecolorallocatealpha($resizedImage, 255, 255, 255, 127);
                    imagefilledrectangle($resizedImage, 0, 0, $newWidth, $newHeight, $transparent);
                }

                // Resize image
                imagecopyresampled(
                    $resizedImage,
                    $sourceImage,
                    0, 0, 0, 0,
                    $newWidth,
                    $newHeight,
                    $originalWidth,
                    $originalHeight
                );

                // Generate size-specific path
                $extension = pathinfo($originalPath, PATHINFO_EXTENSION);
                $sizePath = str_replace(
                    '.' . $extension,
                    "-{$sizeName}.webp",
                    $originalPath
                );

                // Save to temp file
                $tempResized = storage_path('app/temp/' . basename($sizePath));
                imagewebp($resizedImage, $tempResized, 85);

                // Upload to R2
                $disk->put($sizePath, file_get_contents($tempResized));

                // Get public URL
                $generatedSizes[$sizeName] = $r2Service->getPublicUrl($sizePath);

                // Cleanup
                imagedestroy($sourceImage);
                imagedestroy($resizedImage);
                if (file_exists($tempResized)) {
                    unlink($tempResized);
                }

            } catch (\Exception $e) {
                Log::warning('Failed to generate responsive size', [
                    'media_id' => $this->media->id,
                    'size' => $sizeName,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $generatedSizes;
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Image optimization job failed permanently', [
            'media_id' => $this->media->id,
            'shop_id' => $this->media->shop_id,
            'error' => $exception->getMessage(),
        ]);

        // Update media status
        $this->media->update([
            'processing_status' => 'failed',
        ]);
    }
}
