<?php

namespace Src\Core;

class View
{
    public static function render(string $view, array $data = []): void
    {
        global $config;

        extract($data, EXTR_SKIP);

        require dirname(__DIR__) . '/Views/layout/header.php';
        require dirname(__DIR__) . '/Views/' . $view . '.php';
        require dirname(__DIR__) . '/Views/layout/footer.php';
    }
}
