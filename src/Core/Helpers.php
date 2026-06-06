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

    public static function formatDateTime(null|int|string $timestamp): string
    {
        if ($timestamp === null || $timestamp === '') {
            return '-';
        }

        $timestamp = (int) $timestamp;
        if ($timestamp <= 0) {
            return '-';
        }

        return date('d/m/Y H:i:s', $timestamp);
    }

    public static function appUrl(string $path): string
    {
        global $config;
        return rtrim($config['base_url'], '/') . '/' . ltrim($path, '/');
    }

    public static function pagination(string $basePath, int $page, int $totalPages, array $query = []): string
    {
        if ($totalPages <= 1) {
            return '';
        }

        $page = max(1, min($page, $totalPages));
        $items = self::paginationItems($page, $totalPages);
        $html = '<nav class="mt-3 pagination-wrap" aria-label="Pagination"><ul class="pagination mb-0">';
        $html .= self::paginationLink($basePath, 1, 'First', $query, $page === 1);
        $html .= self::paginationLink($basePath, max(1, $page - 1), 'Previous', $query, $page === 1);

        foreach ($items as $item) {
            if ($item === null) {
                $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
                continue;
            }

            $active = $item === $page;
            $html .= self::paginationLink($basePath, $item, (string) $item, $query, false, $active);
        }

        $html .= self::paginationLink($basePath, min($totalPages, $page + 1), 'Next', $query, $page === $totalPages);
        $html .= self::paginationLink($basePath, $totalPages, 'Last', $query, $page === $totalPages);
        $html .= '</ul></nav>';

        return $html;
    }

    private static function paginationItems(int $page, int $totalPages): array
    {
        if ($totalPages <= 7) {
            return range(1, $totalPages);
        }

        $pages = [1];
        $start = max(2, $page - 1);
        $end = min($totalPages - 1, $page + 1);

        if ($page <= 4) {
            $start = 2;
            $end = 5;
        }

        if ($page >= $totalPages - 3) {
            $start = $totalPages - 4;
            $end = $totalPages - 1;
        }

        if ($start > 2) {
            $pages[] = null;
        }

        for ($i = $start; $i <= $end; $i++) {
            $pages[] = $i;
        }

        if ($end < $totalPages - 1) {
            $pages[] = null;
        }

        $pages[] = $totalPages;
        return $pages;
    }

    private static function paginationLink(string $basePath, int $targetPage, string $label, array $query, bool $disabled = false, bool $active = false): string
    {
        $class = 'page-item';
        if ($disabled) {
            $class .= ' disabled';
        }
        if ($active) {
            $class .= ' active';
        }

        if ($disabled || $active) {
            return '<li class="' . $class . '"><span class="page-link">' . self::e($label) . '</span></li>';
        }

        $query = array_filter($query, fn ($value) => $value !== '' && $value !== null && $value !== 0);
        $query['page'] = $targetPage;
        $href = $basePath . '?' . http_build_query($query);

        return '<li class="' . $class . '"><a class="page-link" href="' . self::e($href) . '">' . self::e($label) . '</a></li>';
    }

}

function e(?string $value): string
{
    return Helpers::e($value);
}
