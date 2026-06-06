<?php

namespace Src\Models;

use RuntimeException;
use Src\Core\Auth;
use Src\Core\Database;
use Src\Services\StorageService;
use Src\Services\ThumbnailService;

class FileModel
{
    public static function findById(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT files.*, users.name AS owner_name FROM files JOIN users ON users.id = files.user_id WHERE files.id = ? AND files.deleted_at IS NULL LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function findByToken(string $token): ?array
    {
        $stmt = Database::connection()->prepare('SELECT files.*, users.name AS owner_name FROM files JOIN users ON users.id = files.user_id WHERE files.public_token = ? AND files.deleted_at IS NULL LIMIT 1');
        $stmt->execute([$token]);
        return $stmt->fetch() ?: null;
    }

    public static function totalStorageUsed(): int
    {
        return (int) Database::connection()
            ->query('SELECT COALESCE(SUM(size), 0) FROM files WHERE deleted_at IS NULL')
            ->fetchColumn();
    }

    public static function findDeletedById(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT files.*, users.name AS owner_name FROM files JOIN users ON users.id = files.user_id WHERE files.id = ? AND files.deleted_at IS NOT NULL LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function paginate(int $page, int $perPage, array $filters = []): array
    {
        $user = Auth::user();
        $offset = max(0, ($page - 1) * $perPage);
        $where = ['files.deleted_at IS NULL'];
        $params = [];

        if (!Auth::isAdmin()) {
            $where[] = 'files.user_id = ?';
            $params[] = (int) $user['id'];
        } elseif (!empty($filters['user_id'])) {
            $where[] = 'files.user_id = ?';
            $params[] = (int) $filters['user_id'];
        }

        if (!empty($filters['q'])) {
            $where[] = 'files.original_name LIKE ?';
            $params[] = '%' . $filters['q'] . '%';
        }

        if (!empty($filters['from'])) {
            $where[] = 'DATE(files.created_at) >= ?';
            $params[] = $filters['from'];
        }

        if (!empty($filters['to'])) {
            $where[] = 'DATE(files.created_at) <= ?';
            $params[] = $filters['to'];
        }

        $whereSql = implode(' AND ', $where);

        $countStmt = Database::connection()->prepare("SELECT COUNT(*) FROM files WHERE {$whereSql}");
        $countStmt->execute($params);
        $count = $countStmt->fetchColumn();

        $stmt = Database::connection()->prepare("SELECT files.*, users.name AS owner_name FROM files JOIN users ON users.id = files.user_id WHERE {$whereSql} ORDER BY files.created_at DESC LIMIT ? OFFSET ?");
        foreach ($params as $index => $value) {
            $stmt->bindValue($index + 1, $value);
        }
        $stmt->bindValue(count($params) + 1, $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(count($params) + 2, $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return [
            'items' => $stmt->fetchAll(),
            'total' => (int) $count,
        ];
    }

    public static function createManyFromUpload(array $uploadedFiles): array
    {
        global $config;

        $files = self::normalizeUploadedFiles($uploadedFiles);
        if (!$files) {
            throw new RuntimeException('Please choose at least one file to upload.');
        }

        if (count($files) > $config['upload']['max_files_per_upload']) {
            throw new RuntimeException('You can upload up to ' . $config['upload']['max_files_per_upload'] . ' files at a time.');
        }

        self::ensureStorageQuota($files);

        $created = [];
        foreach ($files as $file) {
            $created[] = self::createSingleFromUpload($file, false);
        }

        return $created;
    }

    public static function createFromUpload(array $uploadedFile): array
    {
        return self::createSingleFromUpload($uploadedFile, true);
    }

    private static function createSingleFromUpload(array $uploadedFile, bool $checkQuota): array
    {
        global $config;

        StorageService::ensureDirectories();

        if (($uploadedFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('File upload failed.');
        }

        if ((int) $uploadedFile['size'] > $config['upload']['max_size']) {
            throw new RuntimeException('The file exceeds the allowed size.');
        }

        $tmpPath = $uploadedFile['tmp_name'];
        if (!is_uploaded_file($tmpPath)) {
            throw new RuntimeException('Invalid upload source.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmpPath);
        $allowed = $config['upload']['allowed_mimes'];

        if (!isset($allowed[$mime])) {
            throw new RuntimeException('Only jpg, png, gif, and webp images are allowed.');
        }

        if (!getimagesize($tmpPath)) {
            throw new RuntimeException('The file is not a valid image.');
        }

        if ($checkQuota) {
            self::ensureStorageQuota([$uploadedFile]);
        }

        $extension = $allowed[$mime];
        $storedName = bin2hex(random_bytes(20)) . '.' . $extension;
        $destination = StorageService::uploadPath($storedName);

        if (!move_uploaded_file($tmpPath, $destination)) {
            throw new RuntimeException('Could not save the uploaded file.');
        }

        $thumbnailPath = ThumbnailService::create($destination, $mime, $storedName);

        $user = Auth::user();
        $stmt = Database::connection()->prepare('INSERT INTO files (user_id, original_name, stored_name, mime_type, extension, size, path, thumbnail_path, public_token, visibility, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, "public", NOW())');
        $stmt->execute([
            $user['id'],
            $uploadedFile['name'],
            $storedName,
            $mime,
            $extension,
            (int) $uploadedFile['size'],
            $destination,
            $thumbnailPath,
            bin2hex(random_bytes(24)),
        ]);

        $fileId = (int) Database::connection()->lastInsertId();
        ActivityLog::create('upload', $fileId);
        return self::findById($fileId);
    }

    public static function findAuthorizedByIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (!$ids) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $params = $ids;
        $where = "files.id IN ({$placeholders}) AND files.deleted_at IS NULL";

        if (!Auth::isAdmin()) {
            $where .= ' AND files.user_id = ?';
            $params[] = (int) Auth::user()['id'];
        }

        $stmt = Database::connection()->prepare("SELECT files.*, users.name AS owner_name FROM files JOIN users ON users.id = files.user_id WHERE {$where} ORDER BY files.created_at DESC");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function paginateDeleted(int $page, int $perPage): array
    {
        $user = Auth::user();
        $offset = max(0, ($page - 1) * $perPage);
        $where = ['files.deleted_at IS NOT NULL'];
        $params = [];

        if (!Auth::isAdmin()) {
            $where[] = 'files.user_id = ?';
            $params[] = (int) $user['id'];
        }

        $whereSql = implode(' AND ', $where);
        $countStmt = Database::connection()->prepare("SELECT COUNT(*) FROM files WHERE {$whereSql}");
        $countStmt->execute($params);
        $count = $countStmt->fetchColumn();

        $stmt = Database::connection()->prepare("SELECT files.*, users.name AS owner_name FROM files JOIN users ON users.id = files.user_id WHERE {$whereSql} ORDER BY files.deleted_at DESC LIMIT ? OFFSET ?");
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

    public static function allDeletedForCurrentUser(): array
    {
        $where = ['files.deleted_at IS NOT NULL'];
        $params = [];

        if (!Auth::isAdmin()) {
            $where[] = 'files.user_id = ?';
            $params[] = (int) Auth::user()['id'];
        }

        $whereSql = implode(' AND ', $where);
        $stmt = Database::connection()->prepare("SELECT files.*, users.name AS owner_name FROM files JOIN users ON users.id = files.user_id WHERE {$whereSql}");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function softDelete(array $file): void
    {
        $stmt = Database::connection()->prepare('UPDATE files SET deleted_at = NOW() WHERE id = ?');
        $stmt->execute([$file['id']]);
        ActivityLog::create('delete', (int) $file['id']);
    }

    public static function softDeleteMany(array $files): void
    {
        foreach ($files as $file) {
            self::softDelete($file);
        }
    }

    public static function updateVisibility(array $file, string $visibility): void
    {
        $visibility = $visibility === 'public' ? 'public' : 'private';
        $stmt = Database::connection()->prepare('UPDATE files SET visibility = ? WHERE id = ?');
        $stmt->execute([$visibility, $file['id']]);
        ActivityLog::create('visibility_' . $visibility, (int) $file['id']);
    }

    public static function restore(array $file): void
    {
        $stmt = Database::connection()->prepare('UPDATE files SET deleted_at = NULL WHERE id = ?');
        $stmt->execute([$file['id']]);
        ActivityLog::create('restore', (int) $file['id']);
    }

    public static function forceDelete(array $file): void
    {
        $pathsToDelete = [$file['path'] ?? null, $file['thumbnail_path'] ?? null];

        Database::connection()->beginTransaction();
        try {
            $stmt = Database::connection()->prepare('DELETE FROM activity_logs WHERE file_id = ?');
            $stmt->execute([$file['id']]);

            $stmt = Database::connection()->prepare('DELETE FROM files WHERE id = ?');
            $stmt->execute([$file['id']]);

            Database::connection()->commit();
        } catch (\Throwable $e) {
            Database::connection()->rollBack();
            throw $e;
        }

        foreach ($pathsToDelete as $path) {
            StorageService::deleteManagedFile($path);
        }
    }

    public static function forceDeleteMany(array $files): int
    {
        $count = 0;
        foreach ($files as $file) {
            self::forceDelete($file);
            $count++;
        }

        return $count;
    }

    public static function purgeExpiredTrash(): int
    {
        global $config;

        $days = max(1, (int) ($config['upload']['trash_retention_days'] ?? 7));
        $threshold = (new \DateTimeImmutable('-' . $days . ' days'))->format('Y-m-d H:i:s');

        $stmt = Database::connection()->prepare(
            'SELECT files.*, users.name AS owner_name
             FROM files
             JOIN users ON users.id = files.user_id
             WHERE files.deleted_at IS NOT NULL
             AND files.deleted_at <= ?'
        );
        $stmt->execute([$threshold]);

        return self::forceDeleteMany($stmt->fetchAll());
    }

    private static function normalizeUploadedFiles(array $uploadedFiles): array
    {
        if (!isset($uploadedFiles['name'])) {
            return [];
        }

        if (!is_array($uploadedFiles['name'])) {
            return [$uploadedFiles];
        }

        $files = [];
        foreach ($uploadedFiles['name'] as $index => $name) {
            if (($uploadedFiles['error'][$index] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $files[] = [
                'name' => $name,
                'type' => $uploadedFiles['type'][$index] ?? '',
                'tmp_name' => $uploadedFiles['tmp_name'][$index] ?? '',
                'error' => $uploadedFiles['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                'size' => $uploadedFiles['size'][$index] ?? 0,
            ];
        }

        return $files;
    }

    private static function ensureStorageQuota(array $files): void
    {
        $user = Auth::user();
        if (!$user || $user['role'] === 'admin') {
            return;
        }

        $incomingSize = array_sum(array_map(fn (array $file): int => (int) ($file['size'] ?? 0), $files));
        $used = UserModel::storageUsed((int) $user['id']);
        $limit = (int) $user['storage_limit'];

        if ($limit > 0 && ($used + $incomingSize) > $limit) {
            throw new RuntimeException('This account has exceeded its storage quota.');
        }
    }

}
