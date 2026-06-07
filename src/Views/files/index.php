<?php

use Src\Core\Csrf;
use Src\Core\Auth;
use Src\Core\Helpers;
use Src\Services\OnlyOfficeService;
use Src\Services\StorageService;

$currentUser = Auth::user();

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
$fileTypes = $fileTypes ?? [];
$uploadAccept = $uploadAccept ?? '';
$uploadMaxSize = (int) ($uploadMaxSize ?? 0);
$uploadMaxSizeLabel = $uploadMaxSize > 0 ? Helpers::formatBytes($uploadMaxSize) : '';
$storageLimit = (int) ($currentUser['storage_limit'] ?? 0);
$storagePercent = $storageLimit > 0 ? min(100, (int) round(((int) $storageUsed / $storageLimit) * 100)) : 0;
$storageStatusClass = $storagePercent >= 90 ? 'is-danger' : ($storagePercent >= 75 ? 'is-warning' : 'is-good');
$diskStats = $diskStats ?? null;
$diskTotal = (int) ($diskStats['total'] ?? 0);
$diskUsable = (int) ($diskStats['usable'] ?? 0);
$diskUsablePercent = $diskTotal > 0 ? max(0, min(100, (int) round(($diskUsable / $diskTotal) * 100))) : 0;
$diskStatusClass = $diskUsablePercent <= 10 ? 'is-danger' : ($diskUsablePercent <= 25 ? 'is-warning' : 'is-good');
$canDeleteAnyFile = $currentUser
    ? count(array_filter($files ?? [], fn (array $file): bool => (int) $file['user_id'] === (int) $currentUser['id'])) > 0
    : false;

$canPreviewFile = function (array $file): bool {
    $mime = (string) ($file['mime_type'] ?? '');

    return $mime === 'application/pdf'
        || $mime === 'text/plain'
        || str_starts_with($mime, 'image/')
        || str_starts_with($mime, 'audio/')
        || str_starts_with($mime, 'video/')
        || (OnlyOfficeService::isConfigured() && OnlyOfficeService::isOfficeFile($file));
};
?>
<div class="page-heading mb-3">
    <div>
        <h1 class="h3 mb-1">File Library</h1>
        <div class="text-muted small"><?= (int) $total ?> file</div>
    </div>
    <?php if ($currentUser): ?>
        <div class="storage-summary">
            <div class="text-muted small">Storage Used</div>
            <?php if ($currentUser['role'] === 'admin'): ?>
                <div class="fw-semibold"><?= Helpers::e(Helpers::formatBytes((int) $storageUsed)) ?> admin used</div>
                <div class="text-muted small"><?= Helpers::e(Helpers::formatBytes($diskUsable)) ?> disk available</div>
                <div
                    class="storage-status-line <?= Helpers::e($diskStatusClass) ?>"
                    role="meter"
                    aria-valuemin="0"
                    aria-valuemax="100"
                    aria-valuenow="<?= (int) $diskUsablePercent ?>"
                    aria-label="Available disk storage"
                >
                    <span style="width: <?= (int) $diskUsablePercent ?>%;"></span>
                </div>
            <?php else: ?>
                <div class="fw-semibold">
                    <?= Helpers::e(Helpers::formatBytes((int) $storageUsed)) ?> / <?= Helpers::e($storageLimit > 0 ? Helpers::formatBytes($storageLimit) : 'Unlimited') ?>
                </div>
                <div
                    class="storage-status-line <?= Helpers::e($storageStatusClass) ?>"
                    role="meter"
                    aria-valuemin="0"
                    aria-valuemax="100"
                    aria-valuenow="<?= (int) $storagePercent ?>"
                    aria-label="Storage usage"
                >
                    <span style="width: <?= (int) $storagePercent ?>%;"></span>
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
                <label
                    id="upload-drop-zone"
                    class="upload-drop-zone"
                    for="file-upload-input"
                    data-max-size="<?= (int) $uploadMaxSize ?>"
                    data-max-size-label="<?= Helpers::e($uploadMaxSizeLabel) ?>"
                >
                    <input id="file-upload-input" class="visually-hidden" type="file" name="files[]" accept="<?= Helpers::e($uploadAccept) ?>" multiple required>
                    <span class="upload-drop-icon"><i class="bi bi-cloud-arrow-up"></i></span>
                    <span class="upload-drop-title">Drop files here or choose files</span>
                    <span class="upload-drop-meta">
                        Multiple files are supported<?= $uploadMaxSize > 0 ? '. Max ' . Helpers::e($uploadMaxSizeLabel) . ' per file.' : '.' ?>
                    </span>
                </label>
            </div>
            <div class="upload-submit">
                <button id="upload-submit-button" class="btn btn-primary" type="submit">
                    <i class="bi bi-cloud-arrow-up"></i>
                    <span>Upload</span>
                </button>
            </div>
            <div class="upload-progress-slot">
                <div id="upload-progress" class="upload-progress" hidden>
                    <div class="upload-progress-meta">
                        <span id="upload-progress-label">Uploading</span>
                        <span id="upload-progress-percent">0%</span>
                    </div>
                    <div class="progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
                        <div id="upload-progress-bar" class="progress-bar" style="width: 0%;"></div>
                    </div>
                </div>
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
            <div class="col-md-2">
                <label class="form-label">Search File Name</label>
                <input class="form-control" name="q" value="<?= Helpers::e($filters['q'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">File Type</label>
                <select class="form-select" name="file_type">
                    <option value="">All</option>
                    <?php foreach ($fileTypes as $fileType): ?>
                        <option value="<?= Helpers::e($fileType) ?>" <?= ($filters['file_type'] ?? '') === $fileType ? 'selected' : '' ?>>
                            <?= Helpers::e(ucfirst($fileType)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if (Auth::isAdmin()): ?>
                <div class="col-md-2">
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
                <th class="text-end">Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$files): ?>
                <tr><td colspan="7" class="text-center text-muted py-5">No files found.</td></tr>
            <?php endif; ?>
            <?php foreach ($files as $file): ?>
                <?php $directUrl = Helpers::appUrl('/view.php?token=' . $file['public_token']); ?>
                <?php $displayName = StorageService::safeDownloadName($file['original_name'], 'file'); ?>
                <?php $displayType = $file['file_type'] ?? (str_starts_with($file['mime_type'] ?? '', 'image/') ? 'image' : 'file'); ?>
                <?php $canPreview = $canPreviewFile($file); ?>
                <?php $canDeleteFile = $currentUser && (int) $file['user_id'] === (int) $currentUser['id']; ?>
                <?php $downloadUrl = '/download.php?id=' . (int) $file['id']; ?>
                <?php $viewUrl = '/view.php?id=' . (int) $file['id']; ?>
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
                            <span class="badge <?= $file['visibility'] === 'public' ? 'text-bg-success' : 'text-bg-secondary' ?>" data-visibility-badge>
                                <i class="bi <?= $file['visibility'] === 'public' ? 'bi-globe2' : 'bi-lock-fill' ?>"></i>
                                <span><?= Helpers::e($file['visibility']) ?></span>
                            </span>
                        </div>
                    </td>
                    <td><span class="badge text-bg-light border"><?= Helpers::e($displayType) ?></span></td>
                    <td><?= Helpers::e($file['owner_name']) ?></td>
                    <td><?= Helpers::e(Helpers::formatBytes((int) $file['size'])) ?></td>
                    <td class="table-actions text-end">
                        <div class="compact-actions justify-content-end">
                            <?php if ($canPreview): ?>
                                <button
                                    class="compact-action text-primary"
                                    type="button"
                                    data-preview-file
                                    data-file-name="<?= Helpers::e($displayName) ?>"
                                    data-file-type="<?= Helpers::e($displayType) ?>"
                                    data-file-mime="<?= Helpers::e($file['mime_type'] ?? '') ?>"
                                    data-view-url="<?= Helpers::e($viewUrl) ?>"
                                    data-download-url="<?= Helpers::e($downloadUrl) ?>"
                                    data-bs-toggle="tooltip"
                                    data-bs-title="View file"
                                >
                                    <i class="bi bi-eye"></i>
                                </button>
                            <?php else: ?>
                                <button
                                    class="compact-action text-primary"
                                    type="button"
                                    data-unpreviewable-file
                                    data-file-name="<?= Helpers::e($displayName) ?>"
                                    data-download-url="<?= Helpers::e($downloadUrl) ?>"
                                    data-bs-toggle="tooltip"
                                    data-bs-title="View file"
                                >
                                    <i class="bi bi-eye"></i>
                                </button>
                            <?php endif; ?>
                            <button
                                class="compact-action"
                                type="button"
                                data-file-detail
                                data-file-id="<?= (int) $file['id'] ?>"
                                data-file-name="<?= Helpers::e($displayName) ?>"
                                data-file-type="<?= Helpers::e($displayType) ?>"
                                data-file-extension="<?= Helpers::e($file['extension'] ?? '-') ?>"
                                data-file-mime="<?= Helpers::e($file['mime_type'] ?? '-') ?>"
                                data-file-owner="<?= Helpers::e($file['owner_name'] ?? '-') ?>"
                                data-file-size="<?= Helpers::e(Helpers::formatBytes((int) $file['size'])) ?>"
                                data-file-uploaded="<?= Helpers::e(Helpers::formatDateTime($file['created_at'])) ?>"
                                data-file-visibility="<?= Helpers::e($file['visibility'] ?? '-') ?>"
                                data-bs-toggle="tooltip"
                                data-bs-title="File details"
                            >
                                <i class="bi bi-info-circle"></i>
                            </button>
                            <button class="compact-action" type="button" data-copy-text="<?= Helpers::e($directUrl) ?>" data-bs-toggle="tooltip" data-bs-title="Copy link">
                                <i class="bi bi-link-45deg"></i>
                            </button>
                            <form method="post" action="/visibility.php" data-visibility-form>
                                <?= Csrf::field() ?>
                                <input type="hidden" name="id" value="<?= (int) $file['id'] ?>">
                                <input type="hidden" name="visibility" value="<?= $file['visibility'] === 'public' ? 'private' : 'public' ?>">
                                <button
                                    class="compact-action <?= $file['visibility'] === 'public' ? 'visibility-public' : 'visibility-private' ?>"
                                    type="submit"
                                    data-bs-toggle="tooltip"
                                    data-bs-title="<?= $file['visibility'] === 'public' ? 'Public - click to make private' : 'Private - click to make public' ?>"
                                >
                                    <i class="bi <?= $file['visibility'] === 'public' ? 'bi-globe2' : 'bi-lock-fill' ?>"></i>
                                </button>
                            </form>
                            <a class="compact-action" href="/download.php?id=<?= (int) $file['id'] ?>" data-bs-toggle="tooltip" data-bs-title="Download original">
                                <i class="bi bi-download"></i>
                            </a>
                            <a class="compact-action" href="/zip.php?id=<?= (int) $file['id'] ?>" data-bs-toggle="tooltip" data-bs-title="Download ZIP">
                                <i class="bi bi-file-zip"></i>
                            </a>
                            <?php if ($canDeleteFile): ?>
                                <form method="post" action="/delete.php" data-confirm="Move this file to trash?">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $file['id'] ?>">
                                    <button class="compact-action text-danger" type="submit" data-bs-toggle="tooltip" data-bs-title="Move to trash">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            <?php else: ?>
                                <button
                                    class="compact-action text-danger is-restricted"
                                    type="button"
                                    data-delete-denied-file
                                    data-bs-toggle="tooltip"
                                    data-bs-title="Cannot delete another user's file"
                                >
                                    <i class="bi bi-trash"></i>
                                </button>
                            <?php endif; ?>
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
        <?php if ($canDeleteAnyFile): ?>
            <button class="btn btn-outline-danger" type="button" data-confirm-form="bulk-delete-form" data-confirm="Move selected files to trash?">
                <i class="bi bi-trash"></i>
                <span>Delete Selected</span>
            </button>
        <?php endif; ?>
    </div>
<?php endif; ?>

<form id="bulk-zip-form" method="post" action="/zip_selected.php"><?= Csrf::field() ?></form>
<form id="bulk-delete-form" method="post" action="/delete_selected.php"><?= Csrf::field() ?></form>
<div class="offcanvas offcanvas-end" tabindex="-1" id="fileDetailDrawer" aria-labelledby="fileDetailTitle">
    <div class="offcanvas-header">
        <h5 id="fileDetailTitle" class="offcanvas-title">File Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <div class="file-detail-list">
            <div class="file-detail-item">
                <span>File ID</span>
                <strong id="detailFileId"></strong>
            </div>
            <div class="file-detail-item">
                <span>Name</span>
                <strong id="detailFileName"></strong>
            </div>
            <div class="file-detail-item">
                <span>Type</span>
                <strong id="detailFileType"></strong>
            </div>
            <div class="file-detail-item">
                <span>Extension</span>
                <strong id="detailFileExtension"></strong>
            </div>
            <div class="file-detail-item">
                <span>MIME</span>
                <strong id="detailFileMime"></strong>
            </div>
            <div class="file-detail-item">
                <span>Owner</span>
                <strong id="detailFileOwner"></strong>
            </div>
            <div class="file-detail-item">
                <span>Size</span>
                <strong id="detailFileSize"></strong>
            </div>
            <div class="file-detail-item">
                <span>Uploaded</span>
                <strong id="detailFileUploaded"></strong>
            </div>
            <div class="file-detail-item">
                <span>Visibility</span>
                <strong id="detailFileVisibility"></strong>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="filePreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="filePreviewTitle">Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="filePreviewBody" class="file-preview-body"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <a id="filePreviewDownload" class="btn btn-primary" href="#">
                    <i class="bi bi-download"></i>
                    <span>Download</span>
                </a>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="unpreviewableFileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cannot Preview File</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="fw-semibold mb-1" id="unpreviewableFileName"></div>
                <div class="text-muted">This file type cannot be viewed directly. Please download it to open with a compatible application.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <a id="unpreviewableFileDownload" class="btn btn-primary" href="#">
                    <i class="bi bi-download"></i>
                    <span>Download</span>
                </a>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="deleteDeniedModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cannot Delete File</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                You can only delete files uploaded by your own account.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const deleteDeniedModalElement = document.getElementById('deleteDeniedModal');
    const deleteDeniedModal = deleteDeniedModalElement ? new bootstrap.Modal(deleteDeniedModalElement) : null;
    document.querySelectorAll('[data-delete-denied-file]').forEach((button) => {
        button.addEventListener('click', () => {
            deleteDeniedModal?.show();
        });
    });

    const detailDrawerElement = document.getElementById('fileDetailDrawer');
    const detailDrawer = detailDrawerElement ? new bootstrap.Offcanvas(detailDrawerElement) : null;
    const detailFields = {
        id: document.getElementById('detailFileId'),
        name: document.getElementById('detailFileName'),
        type: document.getElementById('detailFileType'),
        extension: document.getElementById('detailFileExtension'),
        mime: document.getElementById('detailFileMime'),
        owner: document.getElementById('detailFileOwner'),
        size: document.getElementById('detailFileSize'),
        uploaded: document.getElementById('detailFileUploaded'),
        visibility: document.getElementById('detailFileVisibility'),
    };

    document.querySelectorAll('[data-file-detail]').forEach((button) => {
        button.addEventListener('click', () => {
            detailFields.id.textContent = button.dataset.fileId || '-';
            detailFields.name.textContent = button.dataset.fileName || '-';
            detailFields.type.textContent = button.dataset.fileType || '-';
            detailFields.extension.textContent = button.dataset.fileExtension || '-';
            detailFields.mime.textContent = button.dataset.fileMime || '-';
            detailFields.owner.textContent = button.dataset.fileOwner || '-';
            detailFields.size.textContent = button.dataset.fileSize || '-';
            detailFields.uploaded.textContent = button.dataset.fileUploaded || '-';
            detailFields.visibility.textContent = button.dataset.fileVisibility || '-';
            detailDrawer?.show();
        });
    });

    const previewModalElement = document.getElementById('filePreviewModal');
    const previewModal = previewModalElement ? new bootstrap.Modal(previewModalElement) : null;
    const previewTitle = document.getElementById('filePreviewTitle');
    const previewBody = document.getElementById('filePreviewBody');
    const previewDownload = document.getElementById('filePreviewDownload');

    function clearPreviewBody() {
        if (previewBody) {
            previewBody.innerHTML = '';
        }
    }

    function buildPreviewElement(button) {
        const mime = button.dataset.fileMime || '';
        const type = button.dataset.fileType || '';
        const viewUrl = button.dataset.viewUrl || '#';

        if (mime.startsWith('image/')) {
            const image = document.createElement('img');
            image.className = 'file-preview-image';
            image.src = viewUrl;
            image.alt = button.dataset.fileName || 'Preview';
            return image;
        }

        if (mime.startsWith('audio/')) {
            const audio = document.createElement('audio');
            audio.className = 'file-preview-media';
            audio.src = viewUrl;
            audio.controls = true;
            audio.preload = 'metadata';
            return audio;
        }

        if (mime.startsWith('video/')) {
            const video = document.createElement('video');
            video.className = 'file-preview-media';
            video.src = viewUrl;
            video.controls = true;
            video.preload = 'metadata';
            return video;
        }

        if (mime === 'application/pdf' || mime === 'text/plain' || type === 'document') {
            const iframe = document.createElement('iframe');
            iframe.className = 'file-preview-frame';
            iframe.src = viewUrl;
            iframe.title = button.dataset.fileName || 'Preview';
            return iframe;
        }

        const iframe = document.createElement('iframe');
        iframe.className = 'file-preview-frame';
        iframe.src = viewUrl;
        iframe.title = button.dataset.fileName || 'Preview';
        return iframe;
    }

    document.querySelectorAll('[data-preview-file]').forEach((button) => {
        button.addEventListener('click', () => {
            clearPreviewBody();
            if (previewTitle) {
                previewTitle.textContent = button.dataset.fileName || 'Preview';
            }
            if (previewDownload) {
                previewDownload.href = button.dataset.downloadUrl || '#';
            }
            previewBody?.appendChild(buildPreviewElement(button));
            previewModal?.show();
        });
    });

    previewModalElement?.addEventListener('hidden.bs.modal', clearPreviewBody);

    const unpreviewableModalElement = document.getElementById('unpreviewableFileModal');
    const unpreviewableModal = unpreviewableModalElement ? new bootstrap.Modal(unpreviewableModalElement) : null;
    const unpreviewableFileName = document.getElementById('unpreviewableFileName');
    const unpreviewableFileDownload = document.getElementById('unpreviewableFileDownload');

    document.querySelectorAll('[data-unpreviewable-file]').forEach((button) => {
        button.addEventListener('click', () => {
            if (unpreviewableFileName) {
                unpreviewableFileName.textContent = button.dataset.fileName || 'Selected file';
            }
            if (unpreviewableFileDownload) {
                unpreviewableFileDownload.href = button.dataset.downloadUrl || '#';
            }
            unpreviewableModal?.show();
        });
    });
});

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

const uploadInput = document.getElementById('file-upload-input');
const uploadForm = uploadInput?.closest('form');
const dropZone = document.getElementById('upload-drop-zone');
const preview = document.getElementById('upload-preview');
const uploadSubmitButton = document.getElementById('upload-submit-button');
const uploadProgress = document.getElementById('upload-progress');
const uploadProgressBar = document.getElementById('upload-progress-bar');
const uploadProgressPercent = document.getElementById('upload-progress-percent');
const uploadProgressLabel = document.getElementById('upload-progress-label');
const uploadMaxSize = Number(dropZone?.dataset.maxSize || 0);
const uploadMaxSizeLabel = dropZone?.dataset.maxSizeLabel || '';
let selectedUploadFiles = [];
let isUploadBusy = false;

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
        remove.disabled = isUploadBusy;
        remove.addEventListener('click', () => {
            if (isUploadBusy) {
                return;
            }

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
        if (isUploadBusy) {
            syncUploadInput();
            return;
        }

        selectedUploadFiles = mergeUploadFiles(selectedUploadFiles, Array.from(uploadInput.files));
        syncUploadInput();
        renderUploadPreview();
    });
}

function uploadFileKey(file) {
    return [file.name, file.size, file.lastModified].join(':');
}

function mergeUploadFiles(existingFiles, incomingFiles) {
    const seen = new Set(existingFiles.map(uploadFileKey));
    const merged = [...existingFiles];
    const rejected = [];

    incomingFiles.forEach((file) => {
        if (uploadMaxSize > 0 && file.size > uploadMaxSize) {
            rejected.push(file);
            return;
        }

        const key = uploadFileKey(file);
        if (!seen.has(key)) {
            seen.add(key);
            merged.push(file);
        }
    });

    showRejectedUploadFiles(rejected);

    return merged;
}

function formatClientBytes(bytes) {
    const units = ['B', 'KB', 'MB', 'GB'];
    let size = Number(bytes) || 0;

    for (const unit of units) {
        if (size < 1024 || unit === 'GB') {
            return `${size.toFixed(unit === 'B' ? 0 : 2)} ${unit}`;
        }

        size /= 1024;
    }

    return `${bytes} B`;
}

function showRejectedUploadFiles(files) {
    if (!files.length) {
        return;
    }

    const limitText = uploadMaxSizeLabel || formatClientBytes(uploadMaxSize);
    const names = files.slice(0, 3).map((file) => `${file.name} (${formatClientBytes(file.size)})`).join(', ');
    const more = files.length > 3 ? ` and ${files.length - 3} more` : '';
    showClientToast(`Skipped ${files.length} file(s) over ${limitText}: ${names}${more}.`, 'danger');
}

function setUploadProgress(percent, label = 'Uploading') {
    const normalized = Math.max(0, Math.min(100, Math.round(percent)));
    if (uploadProgress) {
        uploadProgress.hidden = false;
    }
    if (uploadProgressLabel) {
        uploadProgressLabel.textContent = label;
    }
    if (uploadProgressPercent) {
        uploadProgressPercent.textContent = `${normalized}%`;
    }
    if (uploadProgressBar) {
        uploadProgressBar.style.width = `${normalized}%`;
        uploadProgressBar.parentElement?.setAttribute('aria-valuenow', String(normalized));
    }
}

function resetUploadProgress() {
    if (uploadProgress) {
        uploadProgress.hidden = true;
    }
    if (uploadProgressLabel) {
        uploadProgressLabel.textContent = 'Uploading';
    }
    if (uploadProgressPercent) {
        uploadProgressPercent.textContent = '0%';
    }
    if (uploadProgressBar) {
        uploadProgressBar.style.width = '0%';
        uploadProgressBar.parentElement?.setAttribute('aria-valuenow', '0');
    }
}

function setUploadBusy(isBusy) {
    isUploadBusy = isBusy;
    uploadSubmitButton?.toggleAttribute('disabled', isBusy);
    uploadInput?.toggleAttribute('disabled', isBusy);
    dropZone?.classList.toggle('is-disabled', isBusy);
    preview?.classList.toggle('is-disabled', isBusy);
    preview?.querySelectorAll('button').forEach((button) => {
        button.disabled = isBusy;
    });
}

dropZone?.addEventListener('dragenter', (event) => {
    if (isUploadBusy) {
        return;
    }

    event.preventDefault();
    dropZone.classList.add('is-dragover');
});

dropZone?.addEventListener('dragover', (event) => {
    if (isUploadBusy) {
        return;
    }

    event.preventDefault();
    dropZone.classList.add('is-dragover');
});

dropZone?.addEventListener('dragleave', (event) => {
    if (isUploadBusy) {
        return;
    }

    if (!dropZone.contains(event.relatedTarget)) {
        dropZone.classList.remove('is-dragover');
    }
});

dropZone?.addEventListener('drop', (event) => {
    event.preventDefault();
    if (isUploadBusy) {
        return;
    }

    dropZone.classList.remove('is-dragover');
    selectedUploadFiles = mergeUploadFiles(selectedUploadFiles, Array.from(event.dataTransfer?.files || []));
    syncUploadInput();
    renderUploadPreview();
});

uploadForm?.addEventListener('submit', (event) => {
    if (!window.XMLHttpRequest || selectedUploadFiles.length === 0) {
        return;
    }

    const oversizedFiles = selectedUploadFiles.filter((file) => uploadMaxSize > 0 && file.size > uploadMaxSize);
    if (oversizedFiles.length > 0) {
        event.preventDefault();
        selectedUploadFiles = selectedUploadFiles.filter((file) => file.size <= uploadMaxSize);
        showRejectedUploadFiles(oversizedFiles);
        syncUploadInput();
        renderUploadPreview();
        return;
    }

    event.preventDefault();
    const formData = new FormData(uploadForm);
    setUploadBusy(true);
    setUploadProgress(0, 'Uploading');

    const request = new XMLHttpRequest();
    request.open('POST', uploadForm.action);
    request.setRequestHeader('Accept', 'application/json');
    request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

    request.upload.addEventListener('progress', (progressEvent) => {
        if (progressEvent.lengthComputable) {
            setUploadProgress((progressEvent.loaded / progressEvent.total) * 100, 'Uploading');
        }
    });

    request.addEventListener('load', () => {
        let payload = {};
        try {
            payload = JSON.parse(request.responseText || '{}');
        } catch (error) {
            payload = {};
        }

        if (request.status >= 200 && request.status < 300 && payload.ok) {
            setUploadProgress(100, 'Complete');
            showClientToast(payload.message || 'Upload completed.');
            window.location.reload();
            return;
        }

        showClientToast(payload.message || 'Upload failed.', 'danger');
        setUploadBusy(false);
        resetUploadProgress();
    });

    request.addEventListener('error', () => {
        showClientToast('Upload failed. Please try again.', 'danger');
        setUploadBusy(false);
        resetUploadProgress();
    });

    request.send(formData);
});
</script>

<?= Helpers::pagination('/files.php', $page, $totalPages, $filters ?? []) ?>
