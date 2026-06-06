<?php

namespace Src\Models;

use Src\Core\Database;

class UserModel
{
    public static function findActiveById(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT id, name, email, role, status, storage_limit FROM users WHERE id = ? AND status = "active" LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function findById(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT id, name, email, role, status, storage_limit, created_at FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function findActiveByEmail(string $email): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM users WHERE email = ? AND status = "active" LIMIT 1');
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    public static function all(): array
    {
        return Database::connection()
            ->query('SELECT id, name, email, role, status, storage_limit, created_at FROM users ORDER BY created_at DESC')
            ->fetchAll();
    }

    public static function create(string $name, string $email, string $password, string $role, ?int $storageLimit): void
    {
        $stmt = Database::connection()->prepare('INSERT INTO users (name, email, password_hash, role, status, storage_limit, created_at) VALUES (?, ?, ?, ?, "active", ?, UNIX_TIMESTAMP())');
        $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $role, $storageLimit]);
    }

    public static function update(int $id, string $name, string $email, string $role, string $status, ?int $storageLimit): void
    {
        $stmt = Database::connection()->prepare('UPDATE users SET name = ?, email = ?, role = ?, status = ?, storage_limit = ?, updated_at = UNIX_TIMESTAMP() WHERE id = ?');
        $stmt->execute([$name, $email, $role, $status, $storageLimit, $id]);
    }

    public static function updatePassword(int $id, string $password): void
    {
        $stmt = Database::connection()->prepare('UPDATE users SET password_hash = ?, updated_at = UNIX_TIMESTAMP() WHERE id = ?');
        $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
    }

    public static function storageUsed(int $id): int
    {
        $stmt = Database::connection()->prepare('SELECT COALESCE(SUM(size), 0) FROM files WHERE user_id = ? AND deleted_at IS NULL');
        $stmt->execute([$id]);
        return (int) $stmt->fetchColumn();
    }
}
