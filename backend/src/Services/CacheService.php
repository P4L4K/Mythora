<?php
/**
 * MYTHORA - Cache Service
 * Lightweight disk/memory cache for generated scripts, scene descriptions, and API responses
 * Prevents redundant Gemini API calls and stays well within free-tier quotas
 */

namespace App\Services;

class CacheService {
    private string $cacheDir;
    private int $defaultTtl;

    public function __construct(int $defaultTtl = 86400 * 7) {
        $this->cacheDir = __DIR__ . '/../../../storage/cache';
        $this->defaultTtl = $defaultTtl;

        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0777, true);
        }
    }

    private function getFilePath(string $key): string {
        return $this->cacheDir . '/' . hash('sha256', $key) . '.cache';
    }

    public function get(string $key): mixed {
        $file = $this->getFilePath($key);
        if (!file_exists($file)) {
            return null;
        }

        $content = @file_get_contents($file);
        if (!$content) return null;

        $data = @unserialize($content);
        if (!$data || !is_array($data) || !isset($data['expires']) || !isset($data['val'])) {
            return null;
        }

        if (time() > $data['expires']) {
            @unlink($file);
            return null;
        }

        return $data['val'];
    }

    public function set(string $key, mixed $value, ?int $ttl = null): void {
        $file = $this->getFilePath($key);
        $ttl = $ttl ?? $this->defaultTtl;
        $data = [
            'expires' => time() + $ttl,
            'val' => $value
        ];
        @file_put_contents($file, serialize($data));
    }

    public function delete(string $key): void {
        $file = $this->getFilePath($key);
        if (file_exists($file)) {
            @unlink($file);
        }
    }
}

