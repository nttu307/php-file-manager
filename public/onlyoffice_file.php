<?php
require __DIR__ . '/../app/bootstrap.php';

(new \Src\Controllers\FileController())->onlyOfficeFile();
