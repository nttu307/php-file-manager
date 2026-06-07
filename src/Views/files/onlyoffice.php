<?php

use Src\Core\Helpers;
use Src\Services\StorageService;

$displayName = StorageService::safeDownloadName($file['original_name'], 'document');
$apiScript = rtrim($documentServerUrl, '/') . '/web-apps/apps/api/documents/api.js';
$configJson = json_encode($editorConfig, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
?>
<div class="page-heading mb-3">
    <div>
        <h1 class="h4 mb-1"><?= Helpers::e($displayName) ?></h1>
        <div class="text-muted small">OnlyOffice preview</div>
    </div>
    <div class="action-wrap">
        <a class="btn btn-outline-secondary" href="/files.php">
            <i class="bi bi-arrow-left"></i>
            <span>Back</span>
        </a>
        <a class="btn btn-outline-primary" href="/download.php?id=<?= (int) $file['id'] ?>">
            <i class="bi bi-download"></i>
            <span>Download</span>
        </a>
    </div>
</div>

<div class="onlyoffice-frame">
    <div id="onlyoffice-editor"></div>
</div>

<script src="<?= Helpers::e($apiScript) ?>"></script>
<script>
const onlyOfficeConfig = <?= $configJson ?>;
window.docEditor = new DocsAPI.DocEditor('onlyoffice-editor', onlyOfficeConfig);
</script>
