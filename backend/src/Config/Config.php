<?php
/**
 * MYTHORA - Backend Configuration Loader
 */

namespace App\Config;

class Config {
    private static array $env = [];

    public static function load(): void {
        $paths = [
            __DIR__ . '/../../../.env',
            __DIR__ . '/../../../.ENV',
            __DIR__ . '/../../.env',
            __DIR__ . '/../../.ENV'
        ];

        foreach ($paths as $envFile) {
            if (file_exists($envFile)) {
                $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    $trimmed = trim($line);
                    if ($trimmed === '' || str_starts_with($trimmed, '#')) continue;
                    if (!str_contains($trimmed, '=')) continue;
                    [$key, $val] = explode('=', $trimmed, 2);
                    $key = trim($key);
                    $val = trim($val, " \t\n\r\0\x0B\"'");
                    self::$env[$key] = $val;
                    putenv("$key=$val");
                    $_ENV[$key] = $val;
                }
                break;
            }
        }
    }

    public static function get(string $key, mixed $default = null): mixed {
        $val = self::$env[$key] ?? $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if ($val === false || $val === null || $val === '') {
            return $default;
        }
        return $val;
    }
}
