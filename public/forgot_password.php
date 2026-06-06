<?php
require __DIR__ . '/../app/bootstrap.php';

$controller = new \Src\Controllers\AuthController();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller->sendResetLink();
}

$controller->showForgotPassword();
