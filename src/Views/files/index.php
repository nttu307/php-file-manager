<?php

use Src\Core\Csrf;
use Src\Core\Auth;
use Src\Core\Helpers;
use Src\Services\StorageService;

$currentUser = Auth::user();
$query = http_build_query(array_filter($filters ?? [], fn ($value) => $value !== '' && $value !== 0));

$fileTypeIcons = [
    'image' => 'bi-file-image',
    'document' => 'bi-file-earmark-text',
    'archive' => 'bi-file-zip',
    'spreadsheet' => 'bi-file-earmark-spreadsheet',
    'presentation' => 'bi-file-earmark-slides',
    'audio' => 'bi-file-earmark-music',
    'video' => 'bi-file-earmark-play',
    'font' => 'bi-file-earmark-font',
];
?>
<div class="page-heading mb-3">
    <div>
        <h1 class="h3 mb-1">File Library</h1>
        <div class="text-muted small"><?= (int) $total ?> file</div>
    </div>
    <?php if ($currentUser): ?>
        <div class="text-end">
            <div class="text-muted small">Storage Used</div>
            <?php if ($currentUser['role'] === 'admin'): ?>
                <div class="fw-semibold"><?= Helpers::e(Helpers::formatBytes((int) $storageUsed)) ?> total</div>
                <div class="text-muted small">Admin quota: Unlimited</div>
            <?php else: ?>
                <div class="fw-semibold">
                    <?= Helpers::e(Helpers::formatBytes((int) $storageUsed)) ?> / <?= Helpers::e(Helpers::formatBytes((int) ($currentUser['storage_limit'] ?? 0))) ?>
                </div>
                <div class="text-muted small">
                    <?= (int) round(((int) $storageUsed) / 1024 / 1024) ?> / <?= (int) round(((int) ($currentUser['storage_limit'] ?? 0)) / 1024 / 1024) ?> MB
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form class="upload-form" method="post" action="/upload.php" enctype="multipart/form-data">
            <?= Csrf::field() ?>
            <div class="upload-field">
                <label class="form-label">Upload Files</label>
                <input id="image-upload-input" class="form-control" type="file" name="images[]" multiple required>
                <div class="form-text">You can select multiple files in one upload.</div>
            </div>
            <div class="upload-submit">
                <button class="btn btn-primary" type="submit">
                    <i class="bi bi-cloud-arrow-up"></i>
                    <span>Upload</span>
                </button>
            </div>
            <div class="upload-preview-slot">
                <div id="upload-preview" class="upload-preview-grid"></div>
            </div>
        </form>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form class="row g-3 align-items-end" method="get" action="/files.php">
            <div class="col-md-3">
                <label class="form-label">Search File Name</label>
                <input class="form-control" name="q" value="<?= Helpers::e($filters['q'] ?? '') ?>">
            </div>
            <?php if (Auth::isAdmin()): ?>
                <div class="col-md-3">
                    <label class="form-label">User</label>
                    <select class="form-select" name="user_id">
                        <option value="0">All</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?= (int) $user['id'] ?>" <?= (int) ($filters['user_id'] ?? 0) === (int) $user['id'] ? 'selected' : '' ?>>
                                <?= Helpers::e($user['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            <div class="col-md-2">
                <label class="form-label">From Date</label>
                <input class="form-control" type="date" name="from" value="<?= Helpers::e($filters['from'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">To Date</label>
                <input class="form-control" type="date" name="to" value="<?= Helpers::e($filters['to'] ?? '') ?>">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-outline-primary flex-fill" type="submit">Filter</button>
                <a class="btn btn-outline-secondary" href="/files.php">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
            <tr>
                <th style="width: 42px;"></th>
                <th>Preview</th>
                <th>File Name</th>
                <th>Type</th>
                <th>Owner</th>
                <th>Size</th>
                <th>Uploaded At</th>
                <th class="text-end">Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$files): ?>
                <tr><td colspan="8" class="text-center text-muted py-5">No files found.</td></tr>
            <?php endif; ?>
            <?php foreach ($files as $file): ?>
                <?php $directUrl = Helpers::appUrl('/view.php?token=' . $file['public_token']); ?>
                <?php $displayName = StorageService::safeDownloadName($file['original_name'], 'file'); ?>
                <?php $displayType = $file['file_type'] ?? (str_starts_with($file['mime_type'] ?? '', 'image/') ? 'image' : 'file'); ?>
                <tr>
                    <td><input class="form-check-input" type="checkbox" name="ids[]" value="<?= (int) $file['id'] ?>"></td>
                    <td>
                        <?php if ($displayType === 'image'): ?>
                            <img class="thumb" src="/thumb.php?id=<?= (int) $file['id'] ?>" alt="">
                        <?php else: ?>
                            <div class="file-icon-preview">
                                <i class="bi <?= Helpers::e($fileTypeIcons[$displayType] ?? 'bi-file-earmark') ?>"></i>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="fw-semibold file-name-text"><?= Helpers::e($displayName) ?></div>
                            <span class="badge <?= $file['visibility'] === 'public' ? 'text-bg-success' : 'text-bg-secondary' ?>">
                                <?= Helpers::e($file['visibility']) ?>
                            </span>
                        </div>
                    </td>
                    <td><span class="badge text-bg-light border"><?= Helpers::e($displayType) ?></span></td>
                    <td><?= Helpers::e($file['owner_name']) ?></td>
                    <td><?= Helpers::e(Helpers::formatBytes((int) $file['size'])) ?></td>
                    <td><?= Helpers::e($file['created_at']) ?></td>
                    <td class="table-actions text-end">
                        <div class="compact-actions justify-content-end">
                            <a class="compact-action text-primary" href="/view.php?id=<?= (int) $file['id'] ?>" target="_blank" data-bs-toggle="tooltip" data-bs-title="View image">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a class="compact-action" href="/download.php?id=<?= (int) $file['id'] ?>" data-bs-toggle="tooltip" data-bs-title="Download original">
                                <i class="bi bi-download"></i>
                            </a>
                            <a class="compact-action" href="/zip.php?id=<?= (int) $file['id'] ?>" data-bs-toggle="tooltip" data-bs-title="Download ZIP">
                                <i class="bi bi-file-zip"></i>
                            </a>
                            <button class="compact-action" type="button" data-copy-text="<?= Helpers::e($directUrl) ?>" data-bs-toggle="tooltip" data-bs-title="Copy link">
                                <i class="bi bi-link-45deg"></i>
                            </button>
                            <form method="post" action="/visibility.php">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="id" value="<?= (int) $file['id'] ?>">
                                <input type="hidden" name="visibility" value="<?= $file['visibility'] === 'public' ? 'private' : 'public' ?>">
                                <button class="compact-action" type="submit" data-bs-toggle="tooltip" data-bs-title="<?= $file['visibility'] === 'public' ? 'Disable public link' : 'Enable public link' ?>">
                                    <i class="bi <?= $file['visibility'] === 'public' ? 'bi-link-45deg' : 'bi-link' ?>"></i>
                                </button>
                            </form>
                            <form method="post" action="/delete.php" data-confirm="Move this file to trash?">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="id" value="<?= (int) $file['id'] ?>">
                                <button class="compact-action text-danger" type="submit" data-bs-toggle="tooltip" data-bs-title="Move to trash">
                                    <i class="bi bi-trash"></i>
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

<?php if ($files): ?>
    <div class="mt-3 action-wrap align-items-center">
        <button id="select-all-files" class="btn btn-outline-secondary" type="button">
            <i class="bi bi-check2-square"></i>
            <span>Select All</span>
        </button>
        <button id="clear-all-files" class="btn btn-outline-secondary" type="button">
            <i class="bi bi-square"></i>
            <span>Clear Selection</span>
        </button>
        <button class="btn btn-outline-secondary" type="submit" form="bulk-zip-form">
            <i class="bi bi-file-zip"></i>
            <span>Download ZIP</span>
        </button>
        <button class="btn btn-outline-danger" type="button" data-confirm-form="bulk-delete-form" data-confirm="Move selected files to trash?">
            <i class="bi bi-trash"></i>
            <span>Delete Selected</span>
        </button>
    </div>
<?php endif; ?>

<form id="bulk-zip-form" method="post" action="/zip_selected.php"><?= Csrf::field() ?></form>
<form id="bulk-delete-form" method="post" action="/delete_selected.php"><?= Csrf::field() ?></form>
<script>
document.querySelectorAll('input[name="ids[]"]').forEach((checkbox) => {
    checkbox.addEventListener('change', () => {
        document.querySelectorAll('#bulk-zip-form input[name="ids[]"], #bulk-delete-form input[name="ids[]"], #bulk-zip-form input[name="csrf_token"], #bulk-delete-form input[name="csrf_token"]').forEach((input) => input.remove());
        const csrf = '<?= Csrf::token() ?>';
        ['bulk-zip-form', 'bulk-delete-form'].forEach((formId) => {
            const form = document.getElementById(formId);
            const token = document.createElement('input');
            token.type = 'hidden';
            token.name = 'csrf_token';
            token.value = csrf;
            form.appendChild(token);
            document.querySelectorAll('input[name="ids[]"]:checked').forEach((checked) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = checked.value;
                form.appendChild(input);
            });
        });
    });
});

function syncBulkForms() {
    document.querySelectorAll('input[name="ids[]"]').forEach((checkbox) => {
        checkbox.dispatchEvent(new Event('change'));
    });
}

document.getElementById('select-all-files')?.addEventListener('click', () => {
    document.querySelectorAll('input[name="ids[]"]').forEach((checkbox) => {
        checkbox.checked = true;
    });
    syncBulkForms();
});

document.getElementById('clear-all-files')?.addEventListener('click', () => {
    document.querySelectorAll('input[name="ids[]"]').forEach((checkbox) => {
        checkbox.checked = false;
    });
    syncBulkForms();
});

const uploadInput = document.getElementById('image-upload-input');
const preview = document.getElementById('upload-preview');
let selectedUploadFiles = [];

function iconForFile(file) {
    if (file.type.startsWith('image/')) return 'bi-file-image';
    if (file.type === 'application/pdf') return 'bi-file-earmark-pdf';
    if (file.type.includes('zip')) return 'bi-file-zip';
    if (file.type.includes('rar') || file.type.includes('7z') || file.type.includes('gzip') || file.type.includes('tar')) return 'bi-file-zip';
    if (file.type.includes('sheet') || file.type.includes('excel') || file.name.match(/\.(csv|xls|xlsx)$/i)) return 'bi-file-earmark-spreadsheet';
    if (file.type.includes('presentation') || file.type.includes('powerpoint') || file.name.match(/\.(ppt|pptx|odp)$/i)) return 'bi-file-earmark-slides';
    if (file.type.startsWith('audio/') || file.name.match(/\.(mp3|wav|ogg)$/i)) return 'bi-file-earmark-music';
    if (file.type.startsWith('video/') || file.name.match(/\.(mp4|webm|mov)$/i)) return 'bi-file-earmark-play';
    if (file.type.startsWith('font/') || file.name.match(/\.(ttf|otf|woff|woff2)$/i)) return 'bi-file-earmark-font';
    if (file.type.includes('word') || file.name.match(/\.(doc|docx|txt|md|json|xml|yaml|yml|rtf)$/i)) return 'bi-file-earmark-text';
    return 'bi-file-earmark';
}

function syncUploadInput() {
    const dataTransfer = new DataTransfer();
    selectedUploadFiles.forEach((file) => dataTransfer.items.add(file));
    uploadInput.files = dataTransfer.files;
    uploadInput.required = selectedUploadFiles.length === 0;
}

function renderUploadPreview() {
    preview.innerHTML = '';
    selectedUploadFiles.forEach((file, index) => {
        const item = document.createElement('div');
        item.className = 'upload-preview-item';

        let visual;
        if (file.type.startsWith('image/')) {
            visual = document.createElement('img');
            visual.src = URL.createObjectURL(file);
            visual.alt = file.name;
            visual.onload = () => URL.revokeObjectURL(visual.src);
        } else {
            visual = document.createElement('div');
            visual.className = 'upload-file-icon';
            visual.innerHTML = `<i class="bi ${iconForFile(file)}"></i>`;
        }

        const meta = document.createElement('div');
        meta.className = 'upload-preview-meta';
        meta.textContent = file.name;

        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'btn btn-sm btn-outline-danger';
        remove.textContent = 'Remove';
        remove.addEventListener('click', () => {
            selectedUploadFiles.splice(index, 1);
            syncUploadInput();
            renderUploadPreview();
        });

        item.appendChild(visual);
        item.appendChild(meta);
        item.appendChild(remove);
        preview.appendChild(item);
    });
}

if (uploadInput && preview) {
    uploadInput.addEventListener('change', () => {
        selectedUploadFiles = Array.from(uploadInput.files);
        syncUploadInput();
        renderUploadPreview();
    });
}
</script>

<?php if ($totalPages > 1): ?>
    <nav class="mt-3">
        <ul class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="/files.php?<?= $query ? $query . '&' : '' ?>page=<?= $i ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>
