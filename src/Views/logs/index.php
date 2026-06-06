<?php

use Src\Core\Helpers;
?>
<div class="page-heading mb-3">
    <div>
        <h1 class="h3 mb-1">Activity logs</h1>
        <div class="text-muted small"><?= (int) $total ?> log</div>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
            <tr>
                <th>Thoi gian</th>
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
                    <td><?= Helpers::e($log['created_at']) ?></td>
                    <td><?= Helpers::e($log['user_name'] ?? 'System') ?></td>
                    <td><span class="badge text-bg-secondary"><?= Helpers::e($log['action']) ?></span></td>
                    <td><?= Helpers::e($log['file_name'] ?? '-') ?></td>
                    <td><?= Helpers::e($log['ip_address']) ?></td>
                    <td class="text-truncate" style="max-width: 360px;"><?= Helpers::e($log['user_agent']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($totalPages > 1): ?>
    <nav class="mt-3">
        <ul class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="/logs.php?page=<?= $i ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>
