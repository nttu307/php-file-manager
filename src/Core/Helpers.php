<?php

namespace Src\Core;

class Helpers
{
    public static function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }

    public static function redirect(string $path): never
    {
        header('Location: ' . $path);
        exit;
    }

    public static function flash(string $type, string $message): void
    {
        $_SESSION['flash'][$type][] = $message;
    }

    public static function flashes(): array
    {
        $messages = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $messages;
    }

    public static function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = (float) $bytes;
        foreach ($units as $unit) {
            if ($size < 1024 || $unit === 'GB') {
                return number_format($size, $unit === 'B' ? 0 : 2) . ' ' . $unit;
            }
            $size /= 1024;
        }
        return $bytes . ' B';
    }

    public static function appUrl(string $path): string
    {
        global $config;
        return rtrim($config['base_url'], '/') . '/' . ltrim($path, '/');
    }

}

function e(?string $value): string
{
    return Helpers::e($value);
}
