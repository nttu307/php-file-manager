<?php

namespace Src\Services;

use RuntimeException;

class StorageService
{
    public static function ensureDirectories(): void
    {
        global $config;

        foreach (self::managedDirectoryKeys() as $key) {
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

    public static function uploadPath(string $storedName, string $subdir = ''): string
    {
        global $config;
        return self::managedFilePath($config['upload']['dir'], $storedName, $subdir);
    }

    public static function thumbnailPath(string $storedName, string $subdir = ''): string
    {
        global $config;
        return self::managedFilePath($config['upload']['thumbnail_dir'], $storedName, $subdir);
    }

    public static function tempZipPath(): string
    {
        global $config;
        self::ensureDirectories();
        return rtrim($config['upload']['temp_dir'], '/\\') . DIRECTORY_SEPARATOR . bin2hex(random_bytes(16)) . '.zip';
    }

    public static function diskStats(): array
    {
        global $config;

        self::ensureDirectories();

        $path = $config['upload']['dir'];
        $total = disk_total_space($path);
        $free = disk_free_space($path);
        $reserved = max(0, (int) ($config['upload']['reserved_free_space'] ?? 0));

        $total = $total === false ? 0 : (int) $total;
        $free = $free === false ? 0 : (int) $free;

        return [
            'total' => $total,
            'free' => $free,
            'reserved' => $reserved,
            'usable' => max(0, $free - $reserved),
        ];
    }

    public static function ensureDiskSpaceFor(int $incomingSize): void
    {
        $stats = self::diskStats();
        if ($stats['free'] <= 0) {
            throw new RuntimeException('Could not determine available storage space.');
        }

        if ($incomingSize > $stats['usable']) {
            throw new RuntimeException('The server does not have enough available storage space.');
        }
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

        foreach (self::managedDirectoryKeys() as $key) {
            $root = realpath($config['upload'][$key] ?? '');
            if ($root && ($realPath === $root || str_starts_with($realPath, $root . DIRECTORY_SEPARATOR))) {
                return true;
            }
        }

        return false;
    }

    private static function managedDirectoryKeys(): array
    {
        return ['dir', 'thumbnail_dir', 'temp_dir'];
    }

    private static function managedFilePath(string $root, string $storedName, string $subdir = ''): string
    {
        $dir = rtrim($root, '/\\');
        $subdir = self::normalizeSubdir($subdir);

        if ($subdir !== '') {
            $dir .= DIRECTORY_SEPARATOR . $subdir;
        }

        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException('Could not create storage directory.');
        }

        return $dir . DIRECTORY_SEPARATOR . basename($storedName);
    }

    private static function normalizeSubdir(string $subdir): string
    {
        $segments = preg_split('/[\/\\\\]+/', trim($subdir, "/\\"));
        $safeSegments = [];

        foreach ($segments ?: [] as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                continue;
            }

            $safeSegments[] = preg_replace('/[^A-Za-z0-9_-]+/', '-', $segment);
        }

        return implode(DIRECTORY_SEPARATOR, $safeSegments);
    }
}
