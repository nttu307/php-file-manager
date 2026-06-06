<?php

require __DIR__ . '/../app/bootstrap.php';

use Src\Models\FileModel;

if (PHP_SAPI !== 'cli') {
    exit('Run this script from CLI.');
}

$count = FileModel::purgeExpiredTrash();
echo "Expired trash cleanup completed. Deleted {$count} file(s).\n";
