<?php

use Src\Core\Auth;
use Src\Core\Helpers;
use Src\Models\FileModel;

$user = Auth::user();
$trashCount = $user ? FileModel::deletedCountForCurrentUser() : 0;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= Helpers::e($config['app_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/assets/app.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg bg-white sticky-top">
    <div class="container page-shell">
        <a class="navbar-brand fw-semibold" href="/">File Manager</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div id="mainNavbar" class="collapse navbar-collapse">
            <?php if ($user): ?>
                <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
                    <li class="nav-item"><a class="nav-link" href="/files.php">Files</a></li>
                    <li class="nav-item">
                        <a class="nav-link nav-link-notify" href="/trash.php">
                            <span>Trash</span>
                            <?php if ($trashCount > 0): ?>
                                <span class="nav-notification-dot" aria-label="Trash has files"></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <?php if (Auth::isAdmin()): ?>
                        <li class="nav-item"><a class="nav-link" href="/users.php">Users</a></li>
                        <li class="nav-item"><a class="nav-link" href="/logs.php">Logs</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link" href="/profile.php">Profile</a></li>
                    <li class="nav-item">
                        <span class="nav-link text-muted small"><?= Helpers::e($user['name']) ?> · <?= Helpers::e($user['role']) ?></span>
                    </li>
                    <li class="nav-item"><a class="btn btn-sm btn-outline-danger ms-lg-2" href="/logout.php">Logout</a></li>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</nav>
<main class="container page-shell py-4">
<?php $flashMessages = Helpers::flashes(); ?>
<div class="toast-container position-fixed top-0 end-0 p-3">
    <?php foreach ($flashMessages as $type => $messages): ?>
        <?php foreach ($messages as $message): ?>
            <div class="toast align-items-center text-bg-<?= Helpers::e($type) ?> border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="3600">
                <div class="d-flex">
                    <div class="toast-body"><?= Helpers::e($message) ?></div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endforeach; ?>
</div>
