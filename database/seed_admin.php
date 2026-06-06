<?php

require __DIR__ . '/../app/bootstrap.php';

use Src\Core\Database;

if (PHP_SAPI !== 'cli') {
    exit('Run this script from CLI.');
}

[$script, $email, $password, $name] = array_pad($argv, 4, null);

if (!$email || !$password) {
    exit("Usage: php database/seed_admin.php admin@example.com 123456 \"Admin\"\n");
}

$stmt = Database::connection()->prepare('INSERT INTO users (name, email, password_hash, role, status, storage_limit, created_at) VALUES (?, ?, ?, "admin", "active", NULL, NOW())');
$stmt->execute([
    $name ?: 'Admin',
    $email,
    password_hash($password, PASSWORD_DEFAULT),
]);

echo "Admin created: {$email}\n";
