<?php

use Src\Core\Env;

return [
    'app_name' => Env::get('APP_NAME', 'PHP Image Manager'),
    'base_url' => Env::get('APP_URL', 'http://localhost:8000'),
    'db' => [
        'host' => Env::get('DB_HOST', '127.0.0.1'),
        'port' => Env::get('DB_PORT', '3306'),
        'database' => Env::get('DB_DATABASE', 'php_image_manager'),
        'username' => Env::get('DB_USERNAME', 'root'),
        'password' => Env::get('DB_PASSWORD', ''),
        'charset' => Env::get('DB_CHARSET', 'utf8mb4'),
    ],
    'upload' => [
        'dir' => dirname(__DIR__) . '/storage/uploads',
        'thumbnail_dir' => dirname(__DIR__) . '/storage/thumbnails',
        'temp_dir' => dirname(__DIR__) . '/storage/temp',
        'max_size' => (int) Env::get('UPLOAD_MAX_SIZE_MB', 10) * 1024 * 1024,
        'max_files_per_upload' => (int) Env::get('UPLOAD_MAX_FILES_PER_UPLOAD', 10),
        'default_user_storage_limit' => (int) Env::get('DEFAULT_USER_STORAGE_LIMIT_MB', 500) * 1024 * 1024,
        'trash_retention_days' => (int) Env::get('TRASH_RETENTION_DAYS', 7),
        'allowed_mimes' => [
            'image/jpeg' => ['extension' => 'jpg', 'type' => 'image'],
            'image/png' => ['extension' => 'png', 'type' => 'image'],
            'image/gif' => ['extension' => 'gif', 'type' => 'image'],
            'image/webp' => ['extension' => 'webp', 'type' => 'image'],
            'application/pdf' => ['extension' => 'pdf', 'type' => 'document'],
            'text/plain' => ['extension' => 'txt', 'type' => 'document'],
            'text/csv' => ['extension' => 'csv', 'type' => 'document'],
            'application/zip' => ['extension' => 'zip', 'type' => 'archive'],
            'application/x-zip-compressed' => ['extension' => 'zip', 'type' => 'archive'],
            'application/msword' => ['extension' => 'doc', 'type' => 'document'],
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['extension' => 'docx', 'type' => 'document'],
            'application/vnd.ms-excel' => ['extension' => 'xls', 'type' => 'spreadsheet'],
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => ['extension' => 'xlsx', 'type' => 'spreadsheet'],
        ],
    ],
];
