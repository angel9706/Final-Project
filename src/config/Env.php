<?php

namespace App\Config;

class Env
{
    public static function load()
    {
        $envPath = __DIR__ . '/../../.env';
        
        if (!file_exists($envPath)) {
            die("Error: .env file not found at {$envPath}");
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // Parse line
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Remove quotes if present
                if ((strpos($value, '"') === 0 && strrpos($value, '"') === strlen($value) - 1) ||
                    (strpos($value, "'") === 0 && strrpos($value, "'") === strlen($value) - 1)) {
                    $value = substr($value, 1, -1);
                }

                $_ENV[$key] = $value;
                putenv("{$key}={$value}");
            }
        }
        
        // Set timezone
        $timezone = $_ENV['TIMEZONE'] ?? 'Asia/Jakarta';
        date_default_timezone_set($timezone);
    }

    public static function get($key, $default = null)
    {
        return $_ENV[$key] ?? $default;
    }
}
