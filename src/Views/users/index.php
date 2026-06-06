<?php

use Src\Core\Helpers;
?>
<div class="page-heading mb-3">
    <h1 class="h3 mb-0">User Management</h1>
    <a class="btn btn-primary" href="/user_create.php">Create User</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Status</th>
                <th>Quota</th>
                <th>Created At</th>
                <th class="text-end">Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $item): ?>
                <tr>
                    <td><?= Helpers::e($item['name']) ?></td>
                    <td><?= Helpers::e($item['email']) ?></td>
                    <td>
                        <span class="badge <?= $item['role'] === 'admin' ? 'text-bg-primary' : 'text-bg-secondary' ?>">
                            <?= Helpers::e($item['role']) ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge <?= $item['status'] === 'active' ? 'text-bg-success' : 'text-bg-danger' ?>">
                            <?= Helpers::e($item['status']) ?>
                        </span>
                    </td>
                    <td>
                        <?= $item['role'] === 'admin' || $item['storage_limit'] === null ? 'Unlimited' : Helpers::e(Helpers::formatBytes((int) $item['storage_limit'])) ?>
                    </td>
                    <td><?= Helpers::e($item['created_at']) ?></td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-primary icon-btn" href="/user_edit.php?id=<?= (int) $item['id'] ?>" data-bs-toggle="tooltip" data-bs-title="Edit user">
                            <i class="bi bi-pencil-square"></i>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
