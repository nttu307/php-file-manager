<?php

require __DIR__ . '/../app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    (new \Src\Controllers\UserController())->store();
}

(new \Src\Controllers\UserController())->create();
