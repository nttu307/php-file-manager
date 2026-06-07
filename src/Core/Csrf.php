<?php

namespace Src\Core;

class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . Helpers::e(self::token()) . '">';
    }

    public static function verify(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!is_string($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            self::reject();
        }
    }

    private static function reject(): void
    {
        http_response_code(419);
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        if (self::expectsJson()) {
            header('Content-Type: application/json');
            echo json_encode([
                'ok' => false,
                'message' => 'Your session token expired. Please try again.',
            ]);
            exit;
        }

        Helpers::flash('danger', 'Your session token expired. Please try again.');
        Helpers::redirect(self::safeRedirectPath());
    }

    private static function expectsJson(): bool
    {
        return str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
            || strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
    }

    private static function safeRedirectPath(): string
    {
        $referer = (string) ($_SERVER['HTTP_REFERER'] ?? '');
        $path = $referer !== '' ? parse_url($referer, PHP_URL_PATH) : null;

        if (!is_string($path) || $path === '' || !str_starts_with($path, '/')) {
            $path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
        }

        return is_string($path) && $path !== '' && str_starts_with($path, '/') ? $path : '/';
    }
}
