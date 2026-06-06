<?php

require __DIR__ . '/../app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    (new \Src\Controllers\UserController())->update();
}

(new \Src\Controllers\UserController())->edit();
