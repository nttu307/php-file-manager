<?php

require __DIR__ . '/../app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    \Src\Core\Helpers::redirect('/trash.php');
}

(new \Src\Controllers\FileController())->forceDelete();
