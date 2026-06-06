<?php

require __DIR__ . '/../app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    \Src\Core\Helpers::redirect('/files.php');
}

(new \Src\Controllers\FileController())->visibility();
