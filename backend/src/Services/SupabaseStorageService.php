<?php
/**
 * MYTHORA - Supabase Storage Client
 * Handles uploading permanent video and audio assets using the Supabase Storage REST API
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
     */
    public function uploadFile(string $localFilePath, string $destinationPath, string $contentType = 'video/mp4'): string {
        if (empty($this->supabaseUrl) || empty($this->serviceRoleKey) || str_contains($this->supabaseUrl, 'your-project')) {
            // Development fallback: return local relative URL
            return "/renders/" . basename($localFilePath);
        }

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
            CURLOPT_TIMEOUT => 60
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            return "{$this->supabaseUrl}/storage/v1/object/public/{$this->bucket}/{$destinationPath}";
        }

        error_log("Supabase storage upload returned HTTP {$httpCode}: {$response}");
        // Fallback to local path if storage upload is unavailable
        return "/renders/" . basename($localFilePath);
    }
}
