<?php

namespace Src\Core;

use Src\Models\RememberTokenModel;
use Src\Models\UserModel;

class Auth
{
    private const REMEMBER_COOKIE = 'remember_token';

    private static ?array $user = null;

    public static function user(): ?array
    {
        if (empty($_SESSION['user_id'])) {
            return self::loginFromRememberCookie();
        }

        if (self::isSessionExpired()) {
            self::expireSession();
            return null;
        }

        if (self::$user !== null) {
            self::touchSession();
            return self::$user;
        }

        self::$user = UserModel::findActiveById((int) $_SESSION['user_id']);
        if (!self::$user) {
            self::logout();
            return null;
        }

        self::touchSession();
        return self::$user;
    }

    public static function attempt(string $email, string $password, bool $remember = false): bool
    {
        $user = UserModel::findActiveByEmail($email);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }

        self::startSessionForUser((int) $user['id']);
        if ($remember) {
            self::rememberUser((int) $user['id']);
        } else {
            self::forgetRememberCookie();
        }

        return true;
    }

    public static function logout(): void
    {
        self::forgetRememberCookie();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    public static function expireSession(): void
    {
        $_SESSION = [];
        self::$user = null;
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }

        Helpers::flash('warning', 'Your session expired. Please sign in again.');
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

    public static function canDeleteFile(array $file): bool
    {
        $user = self::user();
        if (!$user) {
            return false;
        }

        return (int) $file['user_id'] === (int) $user['id'];
    }

    public static function requireFilePermission(array $file): void
    {
        if (!self::canManageFile($file)) {
            http_response_code(403);
            exit('You do not have permission to perform this action.');
        }
    }

    public static function requireFileDeletePermission(array $file): void
    {
        if (!self::canDeleteFile($file)) {
            http_response_code(403);
            exit('You can only delete files uploaded by your own account.');
        }
    }

    private static function isSessionExpired(): bool
    {
        $lastActivity = (int) ($_SESSION['last_activity'] ?? 0);

        if ($lastActivity <= 0) {
            return false;
        }

        return (time() - $lastActivity) > self::sessionLifetimeSeconds();
    }

    private static function touchSession(): void
    {
        $_SESSION['last_activity'] = time();
    }

    private static function sessionLifetimeSeconds(): int
    {
        global $config;

        return max(60, (int) ($config['session']['lifetime_minutes'] ?? 120) * 60);
    }

    private static function loginFromRememberCookie(): ?array
    {
        $cookie = (string) ($_COOKIE[self::REMEMBER_COOKIE] ?? '');
        [$selector, $token] = self::parseRememberCookie($cookie);
        if ($selector === '' || $token === '') {
            return null;
        }

        $remember = RememberTokenModel::findBySelector($selector);
        if (!$remember || (int) $remember['expires_at'] <= time() || ($remember['user_status'] ?? '') !== 'active') {
            RememberTokenModel::deleteBySelector($selector);
            self::clearRememberCookie();
            return null;
        }

        if (!hash_equals((string) $remember['token_hash'], RememberTokenModel::hashToken($token))) {
            RememberTokenModel::deleteBySelector($selector);
            self::clearRememberCookie();
            return null;
        }

        self::startSessionForUser((int) $remember['user_id']);
        self::rotateRememberToken((int) $remember['id'], $selector);

        self::$user = UserModel::findActiveById((int) $remember['user_id']);
        return self::$user;
    }

    private static function startSessionForUser(int $userId): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        $_SESSION['last_activity'] = time();
        self::$user = null;
    }

    private static function rememberUser(int $userId): void
    {
        RememberTokenModel::deleteExpired();

        $selector = bin2hex(random_bytes(16));
        $token = bin2hex(random_bytes(32));
        $expiresAt = time() + self::rememberLifetimeSeconds();

        RememberTokenModel::create($userId, $selector, $token, $expiresAt);
        self::setRememberCookie($selector, $token, $expiresAt);
    }

    private static function rotateRememberToken(int $id, string $selector): void
    {
        $token = bin2hex(random_bytes(32));
        $expiresAt = time() + self::rememberLifetimeSeconds();

        RememberTokenModel::rotate($id, $token, $expiresAt);
        self::setRememberCookie($selector, $token, $expiresAt);
    }

    private static function forgetRememberCookie(): void
    {
        $cookie = (string) ($_COOKIE[self::REMEMBER_COOKIE] ?? '');
        [$selector] = self::parseRememberCookie($cookie);
        if ($selector !== '') {
            RememberTokenModel::deleteBySelector($selector);
        }

        self::clearRememberCookie();
    }

    private static function parseRememberCookie(string $cookie): array
    {
        $parts = explode(':', $cookie, 2);
        if (count($parts) !== 2) {
            return ['', ''];
        }

        $selector = ctype_xdigit($parts[0]) && strlen($parts[0]) === 32 ? $parts[0] : '';
        $token = ctype_xdigit($parts[1]) && strlen($parts[1]) === 64 ? $parts[1] : '';

        return [$selector, $token];
    }

    private static function setRememberCookie(string $selector, string $token, int $expiresAt): void
    {
        setcookie(self::REMEMBER_COOKIE, $selector . ':' . $token, [
            'expires' => $expiresAt,
            'path' => '/',
            'domain' => '',
            'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private static function clearRememberCookie(): void
    {
        setcookie(self::REMEMBER_COOKIE, '', [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => '',
            'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        unset($_COOKIE[self::REMEMBER_COOKIE]);
    }

    private static function rememberLifetimeSeconds(): int
    {
        global $config;

        return max(86400, (int) ($config['session']['remember_me_days'] ?? 30) * 86400);
    }
}
