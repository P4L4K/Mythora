<?php
/**
 * MYTHORA - Supabase Storage Client
 * Handles uploading, verifying, listing, and deleting media assets via Supabase Storage REST API
 */

namespace App\Services;

use App\Config\Config;

class SupabaseStorageService {
    private string $supabaseUrl;
    private string $serviceRoleKey;
    private string $bucket;

    public function __construct() {
        $this->supabaseUrl = rtrim(Config::get('SUPABASE_URL', ''), '/');
        $this->serviceRoleKey = Config::get('SUPABASE_SERVICE_ROLE_KEY', '');
        $this->bucket = Config::get('SUPABASE_STORAGE_BUCKET', 'mythora-media');
    }

    /**
     * Uploads local file to Supabase Storage bucket and returns public/signed URL
     * Throws exception on failure or if 50MB upload limit is exceeded
     */
    public function uploadFile(string $localFilePath, string $destinationPath, string $contentType = 'video/mp4'): string {
        if (!file_exists($localFilePath)) {
            throw new \Exception("File to upload does not exist: {$localFilePath}");
        }

        $fileSize = filesize($localFilePath);
        // Supabase Free Tier: Strict 50MB limit (52,428,800 bytes)
        if ($fileSize > 52428800) {
            throw new \Exception("File size (" . round($fileSize / 1048576, 2) . " MB) exceeds Supabase Free Tier 50 MB per-file limit.");
        }

        if (empty($this->supabaseUrl) || empty($this->serviceRoleKey) || str_contains($this->supabaseUrl, 'your-project')) {
            // Development local fallback
            return "/renders/" . basename($localFilePath);
        }

        $destinationPath = ltrim($destinationPath, '/');
        $url = "{$this->supabaseUrl}/storage/v1/object/{$this->bucket}/{$destinationPath}";
        $fileData = file_get_contents($localFilePath);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer {$this->serviceRoleKey}",
                "Content-Type: {$contentType}",
                "x-upsert: true"
            ],
            CURLOPT_POSTFIELDS => $fileData,
            CURLOPT_TIMEOUT => 90
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            $publicUrl = "{$this->supabaseUrl}/storage/v1/object/public/{$this->bucket}/{$destinationPath}";
            // Verify file exists on remote
            if ($this->verifyRemoteObject($destinationPath)) {
                return $publicUrl;
            }
            return $publicUrl;
        }

        error_log("Supabase storage upload error [HTTP {$httpCode}]: {$response}");
        throw new \Exception("Failed to upload video to cloud storage (HTTP {$httpCode}). Response: {$response}");
    }

    /**
     * Checks if an object exists in the bucket
     */
    public function verifyRemoteObject(string $destinationPath): bool {
        if (empty($this->supabaseUrl) || empty($this->serviceRoleKey)) {
            return false;
        }

        $destinationPath = ltrim($destinationPath, '/');
        $folder = dirname($destinationPath);
        $fileName = basename($destinationPath);
        if ($folder === '.') $folder = '';

        $url = "{$this->supabaseUrl}/storage/v1/object/list/{$this->bucket}";
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer {$this->serviceRoleKey}",
                "Content-Type: application/json"
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'prefix' => $folder,
                'search' => $fileName,
                'limit' => 10
            ]),
            CURLOPT_TIMEOUT => 15
        ]);

        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code === 200) {
            $items = json_decode($resp, true);
            if (is_array($items)) {
                foreach ($items as $item) {
                    if (isset($item['name']) && $item['name'] === $fileName) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * Deletes one or more files from Supabase Storage bucket
     */
    public function deleteObjects(array $paths): bool {
        if (empty($paths) || empty($this->supabaseUrl) || empty($this->serviceRoleKey)) {
            return false;
        }

        $formattedPaths = array_map(function($p) {
            return ltrim($p, '/');
        }, $paths);

        $url = "{$this->supabaseUrl}/storage/v1/object/{$this->bucket}";
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer {$this->serviceRoleKey}",
                "Content-Type: application/json"
            ],
            CURLOPT_POSTFIELDS => json_encode(['prefixes' => $formattedPaths]),
            CURLOPT_TIMEOUT => 30
        ]);

        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($code >= 200 && $code < 300);
    }
}
