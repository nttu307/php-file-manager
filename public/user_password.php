<?php

require __DIR__ . '/../app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    \Src\Core\Helpers::redirect('/users.php');
}

(new \Src\Controllers\UserController())->password();
