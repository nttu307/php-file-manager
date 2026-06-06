<?php

namespace Src\Models;

use Src\Core\Database;

class ActivityLogModel
{
    public static function paginate(int $page, int $perPage, array $filters = []): array
    {
        $offset = max(0, ($page - 1) * $perPage);
        $where = [];
        $params = [];

        if (!empty($filters['user_id'])) {
            $where[] = 'activity_logs.user_id = ?';
            $params[] = (int) $filters['user_id'];
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $countStmt = Database::connection()->prepare("SELECT COUNT(*) FROM activity_logs {$whereSql}");
        $countStmt->execute($params);
        $count = $countStmt->fetchColumn();

        $stmt = Database::connection()->prepare(
            'SELECT activity_logs.*, users.name AS user_name, files.original_name AS file_name
             FROM activity_logs
             LEFT JOIN users ON users.id = activity_logs.user_id
             LEFT JOIN files ON files.id = activity_logs.file_id
             ' . $whereSql . '
             ORDER BY activity_logs.created_at DESC
             LIMIT ? OFFSET ?'
        );
        foreach ($params as $index => $value) {
            $stmt->bindValue($index + 1, $value, \PDO::PARAM_INT);
        }
        $stmt->bindValue(count($params) + 1, $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(count($params) + 2, $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return [
            'items' => $stmt->fetchAll(),
            'total' => (int) $count,
        ];
    }
}
