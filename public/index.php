<?php
require __DIR__ . '/../app/bootstrap.php';
\Src\Core\Auth::requireLogin();
\Src\Core\Helpers::redirect('/files.php');
