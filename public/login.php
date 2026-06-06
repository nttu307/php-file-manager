<?php
require __DIR__ . '/../app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    (new \Src\Controllers\AuthController())->login();
}

(new \Src\Controllers\AuthController())->showLogin();
