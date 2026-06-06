<?php

namespace Src\Controllers;

use Src\Core\Auth;
use Src\Core\View;
use Src\Models\ActivityLogModel;
use Src\Models\UserModel;

class ActivityLogController
{
    public function index(): void
    {
        Auth::requireAdmin();

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 30;
        $filters = [
            'user_id' => (int) ($_GET['user_id'] ?? 0),
        ];
        $result = ActivityLogModel::paginate($page, $perPage, $filters);
        $totalPages = max(1, (int) ceil($result['total'] / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
            $result = ActivityLogModel::paginate($page, $perPage, $filters);
        }

        View::render('logs/index', [
            'logs' => $result['items'],
            'total' => $result['total'],
            'page' => $page,
            'totalPages' => $totalPages,
            'filters' => $filters,
            'users' => UserModel::all(),
        ]);
    }
}
