<?php

use Src\Core\Csrf;
use Src\Core\Helpers;
?>
<div class="page-heading mb-3">
    <div>
        <h1 class="h3 mb-1">Trash</h1>
        <div class="text-muted small"><?= (int) $total ?> deleted file(s)</div>
    </div>
    <div class="action-wrap">
        <a class="btn btn-outline-secondary" href="/files.php">Back to Files</a>
        <?php if ($total > 0): ?>
            <form method="post" action="/force_delete_all.php" data-confirm="Permanently delete all files in trash?">
                <?= Csrf::field() ?>
                <button class="btn btn-outline-danger" type="submit">
                    <i class="bi bi-trash3"></i>
                    <span>Delete All</span>
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
            <tr>
                <th>File Name</th>
                <th>Owner</th>
                <th>Size</th>
                <th>Deleted At</th>
                <th class="text-end">Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$files): ?>
                <tr><td colspan="5" class="text-center text-muted py-5">Trash is empty.</td></tr>
            <?php endif; ?>
            <?php foreach ($files as $file): ?>
                <tr>
                    <td><?= Helpers::e($file['original_name']) ?></td>
                    <td><?= Helpers::e($file['owner_name']) ?></td>
                    <td><?= Helpers::e(Helpers::formatBytes((int) $file['size'])) ?></td>
                    <td><?= Helpers::e($file['deleted_at']) ?></td>
                    <td class="text-end">
                        <div class="action-wrap justify-content-end">
                        <form class="d-inline" method="post" action="/restore.php">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="id" value="<?= (int) $file['id'] ?>">
                            <button class="btn btn-sm btn-outline-primary icon-btn" type="submit" data-bs-toggle="tooltip" data-bs-title="Restore file">
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </button>
                        </form>
                        <form class="d-inline" method="post" action="/force_delete.php" data-confirm="Permanently delete this file?">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="id" value="<?= (int) $file['id'] ?>">
                            <button class="btn btn-sm btn-outline-danger icon-btn" type="submit" data-bs-toggle="tooltip" data-bs-title="Delete permanently">
                                <i class="bi bi-trash3"></i>
                            </button>
                        </form>
                        </div>
                    </td>
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
                    <a class="page-link" href="/trash.php?page=<?= $i ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>
