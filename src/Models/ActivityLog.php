<?php

namespace Src\Models;

use Src\Core\Auth;
use Src\Core\Database;

class ActivityLog
{
    public static function create(string $action, ?int $fileId = null): void
    {
        $user = Auth::user();
        $stmt = Database::connection()->prepare('INSERT INTO activity_logs (user_id, action, file_id, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, UNIX_TIMESTAMP())');
        $stmt->execute([
            $user['id'] ?? null,
            $action,
            $fileId,
            $_SERVER['REMOTE_ADDR'] ?? '',
            substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        ]);
    }
}
