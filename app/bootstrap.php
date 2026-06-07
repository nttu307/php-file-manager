<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/Core/Env.php';

\Src\Core\Env::load(dirname(__DIR__) . '/.env');

$timezone = \Src\Core\Env::get('APP_TIMEZONE', 'Asia/Ho_Chi_Minh');
if (is_string($timezone) && in_array($timezone, timezone_identifiers_list(), true)) {
    date_default_timezone_set($timezone);
}

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

$config = require __DIR__ . '/config.php';

require dirname(__DIR__) . '/src/Core/Helpers.php';
require dirname(__DIR__) . '/src/Core/Database.php';
require dirname(__DIR__) . '/src/Core/Csrf.php';
require dirname(__DIR__) . '/src/Core/Auth.php';
require dirname(__DIR__) . '/src/Core/View.php';
require dirname(__DIR__) . '/src/Services/MailService.php';
require dirname(__DIR__) . '/src/Services/StorageService.php';
require dirname(__DIR__) . '/src/Services/ThumbnailService.php';
require dirname(__DIR__) . '/src/Services/OnlyOfficeService.php';
require dirname(__DIR__) . '/src/Models/ActivityLog.php';
require dirname(__DIR__) . '/src/Models/ActivityLogModel.php';
require dirname(__DIR__) . '/src/Models/FileModel.php';
require dirname(__DIR__) . '/src/Models/PasswordResetModel.php';
require dirname(__DIR__) . '/src/Models/UserModel.php';
require dirname(__DIR__) . '/src/Controllers/AuthController.php';
require dirname(__DIR__) . '/src/Controllers/FileController.php';
require dirname(__DIR__) . '/src/Controllers/ProfileController.php';
require dirname(__DIR__) . '/src/Controllers/UserController.php';
require dirname(__DIR__) . '/src/Controllers/ActivityLogController.php';
