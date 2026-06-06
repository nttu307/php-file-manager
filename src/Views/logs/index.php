<?php

use Src\Core\Helpers;
?>
<div class="page-heading mb-3">
    <div>
        <h1 class="h3 mb-1">Activity logs</h1>
        <div class="text-muted small"><?= (int) $total ?> log</div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form class="row g-3 align-items-end" method="get" action="/logs.php">
            <div class="col-md-4 col-lg-3">
                <label class="form-label">User</label>
                <select class="form-select" name="user_id">
                    <option value="0">All users</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?= (int) $user['id'] ?>" <?= (int) ($filters['user_id'] ?? 0) === (int) $user['id'] ? 'selected' : '' ?>>
                            <?= Helpers::e($user['name']) ?> - <?= Helpers::e($user['email']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 col-lg-2 d-flex gap-2">
                <button class="btn btn-outline-primary flex-fill" type="submit">Filter</button>
                <a class="btn btn-outline-secondary" href="/logs.php">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
            <tr>
                <th>Time</th>
                <th>User</th>
                <th>Action</th>
                <th>File</th>
                <th>IP</th>
                <th>User agent</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$logs): ?>
                <tr><td colspan="6" class="text-center text-muted py-5">No logs found.</td></tr>
            <?php endif; ?>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= Helpers::e(Helpers::formatDateTime($log['created_at'])) ?></td>
                    <td><?= Helpers::e($log['user_name'] ?? 'System') ?></td>
                    <td><span class="badge text-bg-secondary"><?= Helpers::e($log['action']) ?></span></td>
                    <td><?= Helpers::e($log['file_name'] ?? '-') ?></td>
                    <td><?= Helpers::e($log['ip_address']) ?></td>
                    <td class="log-user-agent"><?= Helpers::e($log['user_agent']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?= Helpers::pagination('/logs.php', $page, $totalPages, $filters ?? []) ?>
