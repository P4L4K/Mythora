<?php
/**
 * VYRAL STUDIO - Backend Configuration Loader
 */

namespace App\Config;

class Config {
    private static array $env = [];

    public static function load(): void {
        $envFile = __DIR__ . '/../../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (str_starts_with(trim($line), '#')) continue;
                if (!str_contains($line, '=')) continue;
                [$key, $val] = explode('=', $line, 2);
                $key = trim($key);
                $val = trim($val, " \t\n\r\0\x0B\"'");
                self::$env[$key] = $val;
                putenv("$key=$val");
            }
        }
    }

    public static function get(string $key, mixed $default = null): mixed {
        return $_ENV[$key] ?? getenv($key) ?: (self::$env[$key] ?? $default);
    }
}

