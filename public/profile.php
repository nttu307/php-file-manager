<?php

require __DIR__ . '/../app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    (new \Src\Controllers\ProfileController())->updatePassword();
}

(new \Src\Controllers\ProfileController())->edit();
