<?php

namespace Src\Services;

class ThumbnailService
{
    public static function create(string $sourcePath, string $mime, string $storedName, string $subdir = ''): ?string
    {
        if (!extension_loaded('gd')) {
            return null;
        }

        $image = match ($mime) {
            'image/jpeg' => imagecreatefromjpeg($sourcePath),
            'image/png' => imagecreatefrompng($sourcePath),
            'image/gif' => imagecreatefromgif($sourcePath),
            'image/webp' => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($sourcePath) : false,
            default => false,
        };

        if (!$image) {
            return null;
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $targetSize = 240;
        $scale = min($targetSize / $width, $targetSize / $height, 1);
        $newWidth = max(1, (int) round($width * $scale));
        $newHeight = max(1, (int) round($height * $scale));

        $thumb = imagecreatetruecolor($newWidth, $newHeight);
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
        imagecopyresampled($thumb, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        $thumbnailPath = StorageService::thumbnailPath($storedName, $subdir);
        match ($mime) {
            'image/jpeg' => imagejpeg($thumb, $thumbnailPath, 82),
            'image/png' => imagepng($thumb, $thumbnailPath, 7),
            'image/gif' => imagegif($thumb, $thumbnailPath),
            'image/webp' => function_exists('imagewebp') ? imagewebp($thumb, $thumbnailPath, 82) : false,
            default => false,
        };

        imagedestroy($image);
        imagedestroy($thumb);

        return is_file($thumbnailPath) ? $thumbnailPath : null;
    }
}
