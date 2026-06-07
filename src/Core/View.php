<?php

namespace Src\Core;

class View
{
    public static function render(string $view, array $data = []): void
    {
        global $config;

        if (!headers_sent()) {
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
        }

        extract($data, EXTR_SKIP);

        require dirname(__DIR__) . '/Views/layout/header.php';
        require dirname(__DIR__) . '/Views/' . $view . '.php';
        require dirname(__DIR__) . '/Views/layout/footer.php';
    }
}
