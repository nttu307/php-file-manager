<?php

namespace Src\Core;

use Src\Models\UserModel;

class Auth
{
    private static ?array $user = null;

    public static function user(): ?array
    {
        if (empty($_SESSION['user_id'])) {
            return null;
        }

        if (self::$user !== null) {
            return self::$user;
        }

        self::$user = UserModel::findActiveById((int) $_SESSION['user_id']);
        if (!self::$user) {
            self::logout();
            return null;
        }

        return self::$user;
    }

    public static function attempt(string $email, string $password): bool
    {
        $user = UserModel::findActiveByEmail($email);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        self::$user = null;
        return true;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    public static function requireLogin(): void
    {
        if (!self::user()) {
            Helpers::redirect('/login.php');
        }
    }

    public static function isAdmin(): bool
    {
        $user = self::user();
        return $user && $user['role'] === 'admin';
    }

    public static function requireAdmin(): void
    {
        self::requireLogin();
        if (!self::isAdmin()) {
            http_response_code(403);
            exit('Access denied.');
        }
    }

    public static function canManageFile(array $file): bool
    {
        $user = self::user();
        if (!$user) {
            return false;
        }

        return $user['role'] === 'admin' || (int) $file['user_id'] === (int) $user['id'];
    }

    public static function requireFilePermission(array $file): void
    {
        if (!self::canManageFile($file)) {
            http_response_code(403);
            exit('You do not have permission to perform this action.');
        }
    }
}
