<?php

namespace Src\Models;

use Src\Core\Database;

class ActivityLogModel
{
    public static function paginate(int $page, int $perPage): array
    {
        $offset = max(0, ($page - 1) * $perPage);
        $count = Database::connection()->query('SELECT COUNT(*) FROM activity_logs')->fetchColumn();

        $stmt = Database::connection()->prepare(
            'SELECT activity_logs.*, users.name AS user_name, files.original_name AS file_name
             FROM activity_logs
             LEFT JOIN users ON users.id = activity_logs.user_id
             LEFT JOIN files ON files.id = activity_logs.file_id
             ORDER BY activity_logs.created_at DESC
             LIMIT ? OFFSET ?'
        );
        $stmt->bindValue(1, $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return [
            'items' => $stmt->fetchAll(),
            'total' => (int) $count,
        ];
    }
}
