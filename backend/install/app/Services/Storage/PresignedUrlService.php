<?php

namespace App\Services\Storage;

use Aws\S3\S3Client;
use Illuminate\Support\Facades\Log;

/**
 * Cloudflare R2 Presigned URL Service
 *
 * Handles secure file uploads/downloads using presigned URLs
 * with Cloudflare R2 (S3-compatible storage)
 */
class PresignedUrlService
{
    private S3Client $client;
    private string $bucket;
    private string $publicUrl;

    public function __construct()
    {
        $config = config('filesystems.disks.r2');

        $this->client = new S3Client([
            'version' => 'latest',
            'region' => 'auto',
            'endpoint' => $config['endpoint'],
            'credentials' => [
                'key' => $config['key'],
                'secret' => $config['secret'],
            ],
            'use_path_style_endpoint' => false,
        ]);

        $this->bucket = $config['bucket'];
        $this->publicUrl = $config['url'];
    }

    /**
     * Generate presigned URL for uploading files
     *
     * @param string $path File path in bucket (e.g., 'products/shop-123/uuid.jpg')
     * @param int $expiresIn Expiration in minutes (default: 15)
     * @param string $contentType MIME type
     * @param string $visibility 'public' or 'private' (default: public)
     * @return array ['url' => string, 'headers' => array, 'key' => string, 'expires_at' => string]
     */
    public function generateUploadUrl(
        string $path,
        int $expiresIn = 15,
        string $contentType = 'application/octet-stream',
        string $visibility = 'public'
    ): array {
        $key = trim($path, '/');

        $params = [
            'Bucket' => $this->bucket,
            'Key' => $key,
            'ContentType' => $contentType,
        ];

        // Set cache control and ACL for public files
        if ($visibility === 'public') {
            $params['CacheControl'] = 'max-age=31536000, public';
            $params['ACL'] = 'public-read';
        }

        $cmd = $this->client->getCommand('PutObject', $params);
        $request = $this->client->createPresignedRequest($cmd, "+{$expiresIn} minutes");

        return [
            'url' => (string) $request->getUri(),
            'method' => 'PUT',
            'headers' => [
                'Content-Type' => $contentType,
            ],
            'key' => $key,
            'expires_at' => now()->addMinutes($expiresIn)->toIso8601String(),
        ];
    }

    /**
     * Generate presigned URL for downloading private files
     *
     * @param string $path File path in bucket
     * @param int $expiresIn Expiration in minutes (default: 60)
     * @param string|null $filename Force download with specific filename
     * @return string Presigned download URL
     */
    public function generateDownloadUrl(
        string $path,
        int $expiresIn = 60,
        ?string $filename = null
    ): string {
        $params = [
            'Bucket' => $this->bucket,
            'Key' => trim($path, '/'),
        ];

        // Force download with custom filename
        if ($filename) {
            $params['ResponseContentDisposition'] = 'attachment; filename="' . $filename . '"';
        }

        $cmd = $this->client->getCommand('GetObject', $params);
        $request = $this->client->createPresignedRequest($cmd, "+{$expiresIn} minutes");

        return (string) $request->getUri();
    }

    /**
     * Get public URL for file (no expiration, uses CDN)
     *
     * @param string $path File path in bucket
     * @return string Public CDN URL
     */
    public function getPublicUrl(string $path): string
    {
        return rtrim($this->publicUrl, '/') . '/' . ltrim($path, '/');
    }

    /**
     * Delete file from R2
     *
     * @param string $path File path in bucket
     * @return bool Success status
     */
    public function delete(string $path): bool
    {
        try {
            $this->client->deleteObject([
                'Bucket' => $this->bucket,
                'Key' => trim($path, '/'),
            ]);

            Log::info('R2 file deleted', ['path' => $path]);
            return true;

        } catch (\Exception $e) {
            Log::error('R2 delete failed', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Delete multiple files from R2
     *
     * @param array $paths Array of file paths
     * @return array ['deleted' => array, 'failed' => array]
     */
    public function deleteMultiple(array $paths): array
    {
        $deleted = [];
        $failed = [];

        foreach ($paths as $path) {
            if ($this->delete($path)) {
                $deleted[] = $path;
            } else {
                $failed[] = $path;
            }
        }

        return [
            'deleted' => $deleted,
            'failed' => $failed,
        ];
    }

    /**
     * Check if file exists in R2
     *
     * @param string $path File path in bucket
     * @return bool
     */
    public function exists(string $path): bool
    {
        try {
            $this->client->headObject([
                'Bucket' => $this->bucket,
                'Key' => trim($path, '/'),
            ]);

            return true;

        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get file size in bytes
     *
     * @param string $path File path in bucket
     * @return int|null File size in bytes, null if not found
     */
    public function getFileSize(string $path): ?int
    {
        try {
            $result = $this->client->headObject([
                'Bucket' => $this->bucket,
                'Key' => trim($path, '/'),
            ]);

            return (int) $result['ContentLength'];

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Copy file within R2 bucket
     *
     * @param string $sourcePath Source file path
     * @param string $destinationPath Destination file path
     * @return bool Success status
     */
    public function copy(string $sourcePath, string $destinationPath): bool
    {
        try {
            $this->client->copyObject([
                'Bucket' => $this->bucket,
                'CopySource' => $this->bucket . '/' . trim($sourcePath, '/'),
                'Key' => trim($destinationPath, '/'),
            ]);

            Log::info('R2 file copied', [
                'from' => $sourcePath,
                'to' => $destinationPath,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('R2 copy failed', [
                'from' => $sourcePath,
                'to' => $destinationPath,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
