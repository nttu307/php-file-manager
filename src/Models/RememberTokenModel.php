<?php

namespace Src\Models;

use Src\Core\Database;

class RememberTokenModel
{
    public static function create(int $userId, string $selector, string $token, int $expiresAt): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO remember_tokens (user_id, selector, token_hash, expires_at, created_at) VALUES (?, ?, ?, ?, UNIX_TIMESTAMP())'
        );
        $stmt->execute([$userId, $selector, self::hashToken($token), $expiresAt]);
    }

    public static function findBySelector(string $selector): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT remember_tokens.*, users.status AS user_status
             FROM remember_tokens
             JOIN users ON users.id = remember_tokens.user_id
             WHERE remember_tokens.selector = ?
             LIMIT 1'
        );
        $stmt->execute([$selector]);

        return $stmt->fetch() ?: null;
    }

    public static function rotate(int $id, string $token, int $expiresAt): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE remember_tokens SET token_hash = ?, expires_at = ?, last_used_at = UNIX_TIMESTAMP() WHERE id = ?'
        );
        $stmt->execute([self::hashToken($token), $expiresAt, $id]);
    }

    public static function deleteBySelector(string $selector): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM remember_tokens WHERE selector = ?');
        $stmt->execute([$selector]);
    }

    public static function deleteExpired(): void
    {
        Database::connection()
            ->prepare('DELETE FROM remember_tokens WHERE expires_at <= UNIX_TIMESTAMP()')
            ->execute();
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }
}
