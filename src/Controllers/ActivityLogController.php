<?php

namespace Src\Controllers;

use Src\Core\Auth;
use Src\Core\View;
use Src\Models\ActivityLogModel;

class ActivityLogController
{
    public function index(): void
    {
        Auth::requireAdmin();

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 30;
        $result = ActivityLogModel::paginate($page, $perPage);

        View::render('logs/index', [
            'logs' => $result['items'],
            'total' => $result['total'],
            'page' => $page,
            'totalPages' => max(1, (int) ceil($result['total'] / $perPage)),
        ]);
    }
}
