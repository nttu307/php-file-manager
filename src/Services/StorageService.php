<?php

namespace Src\Services;

use RuntimeException;

class StorageService
{
    public static function ensureDirectories(): void
    {
        global $config;

        foreach (['dir', 'thumbnail_dir', 'temp_dir'] as $key) {
            $path = $config['upload'][$key] ?? null;
            if (!$path) {
                continue;
            }

            if (!is_dir($path) && !mkdir($path, 0755, true) && !is_dir($path)) {
                throw new RuntimeException('Could not create storage directory.');
            }
        }
    }

    public static function deleteManagedFile(?string $path): void
    {
        if ($path && self::isManagedPath($path) && is_file($path)) {
            unlink($path);
        }
    }

    public static function uploadPath(string $storedName): string
    {
        global $config;
        return rtrim($config['upload']['dir'], '/\\') . DIRECTORY_SEPARATOR . $storedName;
    }

    public static function thumbnailPath(string $storedName): string
    {
        global $config;
        return rtrim($config['upload']['thumbnail_dir'], '/\\') . DIRECTORY_SEPARATOR . $storedName;
    }

    public static function tempZipPath(): string
    {
        global $config;
        self::ensureDirectories();
        return rtrim($config['upload']['temp_dir'], '/\\') . DIRECTORY_SEPARATOR . bin2hex(random_bytes(16)) . '.zip';
    }

    public static function safeDownloadName(string $name, string $fallback = 'download'): string
    {
        $name = basename(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $name));
        $name = preg_replace('/[^\w.\- ()]+/u', '_', $name) ?: $fallback;
        $name = trim($name, " .\t\n\r\0\x0B");

        return $name !== '' ? $name : $fallback;
    }

    private static function isManagedPath(string $path): bool
    {
        global $config;

        $realPath = realpath($path);
        if (!$realPath) {
            return false;
        }

        foreach (['dir', 'thumbnail_dir', 'temp_dir'] as $key) {
            $root = realpath($config['upload'][$key] ?? '');
            if ($root && str_starts_with($realPath, $root . DIRECTORY_SEPARATOR)) {
                return true;
            }
        }

        return false;
    }
}
