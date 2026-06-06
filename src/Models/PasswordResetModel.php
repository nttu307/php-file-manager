<?php

namespace Src\Models;

use Src\Core\Database;

class PasswordResetModel
{
    public static function create(int $userId, int $ttlMinutes): string
    {
        self::invalidateUserTokens($userId);

        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $ttlMinutes = max(1, $ttlMinutes);

        $stmt = Database::connection()->prepare(
            'INSERT INTO password_resets (user_id, token_hash, expires_at, created_at) VALUES (?, ?, UNIX_TIMESTAMP() + (? * 60), UNIX_TIMESTAMP())'
        );
        $stmt->execute([$userId, $tokenHash, $ttlMinutes]);

        return $token;
    }

    public static function findValidByToken(string $token): ?array
    {
        $token = trim($token);
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }

        $stmt = Database::connection()->prepare(
            'SELECT password_resets.*, users.name AS user_name, users.email AS user_email
             FROM password_resets
             JOIN users ON users.id = password_resets.user_id
             WHERE password_resets.token_hash = ?
             AND password_resets.used_at IS NULL
             AND password_resets.expires_at > UNIX_TIMESTAMP()
             AND users.status = "active"
             LIMIT 1'
        );
        $stmt->execute([hash('sha256', $token)]);

        return $stmt->fetch() ?: null;
    }

    public static function markUsed(int $id): void
    {
        $stmt = Database::connection()->prepare('UPDATE password_resets SET used_at = UNIX_TIMESTAMP() WHERE id = ?');
        $stmt->execute([$id]);
    }

    private static function invalidateUserTokens(int $userId): void
    {
        $stmt = Database::connection()->prepare('UPDATE password_resets SET used_at = UNIX_TIMESTAMP() WHERE user_id = ? AND used_at IS NULL');
        $stmt->execute([$userId]);
    }
}
