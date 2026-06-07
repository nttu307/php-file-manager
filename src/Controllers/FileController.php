<?php

namespace Src\Controllers;

use Throwable;
use ZipArchive;
use Src\Core\Auth;
use Src\Core\Csrf;
use Src\Core\Helpers;
use Src\Core\View;
use Src\Models\FileModel;
use Src\Models\UserModel;
use Src\Services\OnlyOfficeService;
use Src\Services\StorageService;

class FileController
{
    public function index(): void
    {
        Auth::requireLogin();
        FileModel::purgeExpiredTrash();

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 12;
        $fileTypes = $this->configuredFileTypes();
        $requestedFileType = trim($_GET['file_type'] ?? '');
        if (!in_array($requestedFileType, $fileTypes, true)) {
            $requestedFileType = '';
        }

        $filters = [
            'q' => trim($_GET['q'] ?? ''),
            'file_type' => $requestedFileType,
            'user_id' => (int) ($_GET['user_id'] ?? 0),
            'from' => trim($_GET['from'] ?? ''),
            'to' => trim($_GET['to'] ?? ''),
        ];
        $result = FileModel::paginate($page, $perPage, $filters);
        $totalPages = max(1, (int) ceil($result['total'] / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
            $result = FileModel::paginate($page, $perPage, $filters);
        }
        $user = Auth::user();
        $storageUsed = 0;
        if ($user) {
            $storageUsed = UserModel::storageUsed((int) $user['id']);
        }

        View::render('files/index', [
            'files' => $result['items'],
            'total' => $result['total'],
            'page' => $page,
            'totalPages' => $totalPages,
            'filters' => $filters,
            'users' => Auth::isAdmin() ? UserModel::all() : [],
            'storageUsed' => $storageUsed,
            'diskStats' => Auth::isAdmin() ? StorageService::diskStats() : null,
            'uploadAccept' => $this->uploadAccept(),
            'uploadMaxSize' => $this->uploadMaxSize(),
            'fileTypes' => $fileTypes,
        ]);
    }

    public function upload(): void
    {
        Auth::requireLogin();
        Csrf::verify();

        try {
            $created = FileModel::createManyFromUpload($_FILES['files'] ?? $_FILES['images'] ?? []);
            if ($this->expectsJson()) {
                $this->json([
                    'ok' => true,
                    'message' => 'Uploaded ' . count($created) . ' file(s).',
                    'count' => count($created),
                ]);
            }
            Helpers::flash('success', 'Uploaded ' . count($created) . ' file(s).');
        } catch (Throwable $e) {
            if ($this->expectsJson()) {
                $this->json([
                    'ok' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }
            Helpers::flash('danger', $e->getMessage());
        }

        Helpers::redirect('/files.php');
    }

    public function view(): void
    {
        $file = null;
        if (isset($_GET['token'])) {
            $file = FileModel::findByToken((string) $_GET['token']);
        } elseif (isset($_GET['id'])) {
            Auth::requireLogin();
            $file = FileModel::findById((int) $_GET['id']);
            if ($file) {
                Auth::requireFilePermission($file);
            }
        }

        if (!$file) {
            http_response_code(404);
            exit('File not found.');
        }

        if (isset($_GET['token']) && $file['visibility'] !== 'public') {
            $this->sendPrivateLinkPlaceholder();
        }

        if (!isset($_GET['token'])) {
            Auth::requireLogin();
            Auth::requireFilePermission($file);
        }

        if (OnlyOfficeService::isOfficeFile($file)) {
            if (!OnlyOfficeService::isConfigured()) {
                $this->sendFile($file, true);
            }

            View::render('files/onlyoffice', [
                'file' => $file,
                'documentServerUrl' => OnlyOfficeService::documentServerUrl(),
                'editorConfig' => OnlyOfficeService::editorConfig($file, Auth::user()),
            ]);
            return;
        }

        $this->sendFile($file, false);
    }

    public function onlyOfficeFile(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $expiresAt = (int) ($_GET['expires'] ?? 0);
        $signature = (string) ($_GET['signature'] ?? '');

        if (!OnlyOfficeService::validateSourceRequest($id, $expiresAt, $signature)) {
            http_response_code(403);
            exit('Invalid or expired file token.');
        }

        $file = FileModel::findById($id);
        if (!$file || !OnlyOfficeService::isOfficeFile($file)) {
            http_response_code(404);
            exit('File not found.');
        }

        $this->sendFile($file, true);
    }

    public function onlyOfficeCallback(): void
    {
        header('Content-Type: application/json');
        echo json_encode(['error' => 0]);
        exit;
    }

    public function thumbnail(): void
    {
        Auth::requireLogin();
        $file = FileModel::findById((int) ($_GET['id'] ?? 0));
        if (!$file) {
            http_response_code(404);
            exit('File not found.');
        }

        Auth::requireFilePermission($file);

        $isImage = ($file['file_type'] ?? '') === 'image' || str_starts_with($file['mime_type'] ?? '', 'image/');
        if (!$isImage) {
            http_response_code(404);
            exit('Thumbnail not available.');
        }

        if (!empty($file['thumbnail_path']) && is_file($file['thumbnail_path'])) {
            $thumb = $file;
            $thumb['path'] = $file['thumbnail_path'];
            $this->sendFile($thumb, false);
        }

        $this->sendFile($file, false);
    }

    public function download(): void
    {
        Auth::requireLogin();
        $file = $this->authorizedFileFromId();
        $this->sendFile($file, true);
    }

    public function delete(): void
    {
        Auth::requireLogin();
        Csrf::verify();

        $file = FileModel::findById((int) ($_POST['id'] ?? 0));
        if (!$file) {
            Helpers::flash('danger', 'File not found.');
            Helpers::redirect('/files.php');
        }

        if (!Auth::canDeleteFile($file)) {
            Helpers::flash('danger', 'You can only delete files uploaded by your own account.');
            Helpers::redirect('/files.php');
        }

        FileModel::softDelete($file);
        Helpers::flash('success', 'File moved to trash.');
        Helpers::redirect('/files.php');
    }

    public function deleteSelected(): void
    {
        Auth::requireLogin();
        Csrf::verify();

        $files = FileModel::findAuthorizedByIds($_POST['ids'] ?? []);
        if (!$files) {
            Helpers::flash('danger', 'Please select at least one valid file.');
            Helpers::redirect('/files.php');
        }

        foreach ($files as $file) {
            if (!Auth::canDeleteFile($file)) {
                Helpers::flash('danger', 'You can only delete files uploaded by your own account.');
                Helpers::redirect('/files.php');
            }
        }

        FileModel::softDeleteMany($files);
        Helpers::flash('success', 'Moved ' . count($files) . ' file(s) to trash.');
        Helpers::redirect('/files.php');
    }

    public function visibility(): void
    {
        Auth::requireLogin();
        Csrf::verify();

        $file = FileModel::findById((int) ($_POST['id'] ?? 0));
        if (!$file) {
            if ($this->expectsJson()) {
                $this->json(['ok' => false, 'message' => 'File not found.'], 404);
            }
            Helpers::flash('danger', 'File not found.');
            Helpers::redirect('/files.php');
        }

        Auth::requireFilePermission($file);
        $visibility = ($_POST['visibility'] ?? '') === 'public' ? 'public' : 'private';
        FileModel::updateVisibility($file, $visibility);

        if ($this->expectsJson()) {
            $this->json([
                'ok' => true,
                'visibility' => $visibility,
                'next_visibility' => $visibility === 'public' ? 'private' : 'public',
                'message' => $visibility === 'public' ? 'Public link enabled.' : 'Public link disabled.',
            ]);
        }

        Helpers::flash('success', $visibility === 'public' ? 'Public link enabled.' : 'Public link disabled.');
        Helpers::redirect('/files.php');
    }

    public function trash(): void
    {
        Auth::requireLogin();
        FileModel::purgeExpiredTrash();

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 12;
        $result = FileModel::paginateDeleted($page, $perPage);
        $totalPages = max(1, (int) ceil($result['total'] / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
            $result = FileModel::paginateDeleted($page, $perPage);
        }

        View::render('files/trash', [
            'files' => $result['items'],
            'total' => $result['total'],
            'page' => $page,
            'totalPages' => $totalPages,
        ]);
    }

    public function restore(): void
    {
        Auth::requireLogin();
        Csrf::verify();

        $file = FileModel::findDeletedById((int) ($_POST['id'] ?? 0));
        if (!$file) {
            Helpers::flash('danger', 'File not found in trash.');
            Helpers::redirect('/trash.php');
        }

        Auth::requireFileDeletePermission($file);
        FileModel::restore($file);
        Helpers::flash('success', 'File restored successfully.');
        Helpers::redirect('/trash.php');
    }

    public function forceDelete(): void
    {
        Auth::requireLogin();
        Csrf::verify();

        $file = FileModel::findDeletedById((int) ($_POST['id'] ?? 0));
        if (!$file) {
            Helpers::flash('danger', 'File not found in trash.');
            Helpers::redirect('/trash.php');
        }

        Auth::requireFileDeletePermission($file);
        FileModel::forceDelete($file);
        Helpers::flash('success', 'File permanently deleted.');
        Helpers::redirect('/trash.php');
    }

    public function forceDeleteAll(): void
    {
        Auth::requireLogin();
        Csrf::verify();

        $files = FileModel::allDeletedForCurrentUser();
        if (!$files) {
            Helpers::flash('danger', 'Trash is already empty.');
            Helpers::redirect('/trash.php');
        }

        $count = FileModel::forceDeleteMany($files);
        Helpers::flash('success', 'Permanently deleted ' . $count . ' file(s).');
        Helpers::redirect('/trash.php');
    }

    public function zip(): void
    {
        Auth::requireLogin();

        $file = $this->authorizedFileFromId();
        $this->sendZip([$file], pathinfo(StorageService::safeDownloadName($file['original_name'], 'file'), PATHINFO_FILENAME) . '.zip');
    }

    public function zipSelected(): void
    {
        Auth::requireLogin();
        Csrf::verify();

        $files = FileModel::findAuthorizedByIds($_POST['ids'] ?? []);
        if (!$files) {
            Helpers::flash('danger', 'Please select at least one valid file.');
            Helpers::redirect('/files.php');
        }

        $this->sendZip($files, 'selected-files.zip');
    }

    private function sendZip(array $files, string $downloadName): void
    {
        global $config;

        if (!class_exists(ZipArchive::class)) {
            http_response_code(500);
            exit('The ZipArchive extension is not enabled on this server.');
        }

        $zipPath = StorageService::tempZipPath();

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            http_response_code(500);
            exit('Could not create ZIP file.');
        }

        foreach ($files as $file) {
            if (is_file($file['path'])) {
                $zip->addFile($file['path'], StorageService::safeDownloadName($file['original_name'], 'file'));
            }
        }
        $zip->close();

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . StorageService::safeDownloadName($downloadName, 'files.zip') . '"');
        header('Content-Length: ' . filesize($zipPath));
        readfile($zipPath);
        unlink($zipPath);
        exit;
    }

    private function authorizedFileFromId(): array
    {
        $file = FileModel::findById((int) ($_GET['id'] ?? 0));
        if (!$file) {
            http_response_code(404);
            exit('File not found.');
        }

        Auth::requireFilePermission($file);
        return $file;
    }

    private function sendFile(array $file, bool $download): void
    {
        if (!is_file($file['path'])) {
            http_response_code(404);
            exit('The file does not exist on the server.');
        }

        if (!$download && !$this->canInline($file)) {
            $download = true;
        }

        header('X-Content-Type-Options: nosniff');
        if (!$download) {
            header('Content-Security-Policy: sandbox');
        }
        header('Accept-Ranges: bytes');
        header('Content-Type: ' . $file['mime_type']);
        header('Content-Disposition: ' . ($download ? 'attachment' : 'inline') . '; filename="' . StorageService::safeDownloadName($file['original_name'], 'file') . '"');

        $fileSize = filesize($file['path']);
        $start = 0;
        $end = $fileSize - 1;

        if (isset($_SERVER['HTTP_RANGE']) && preg_match('/bytes=(\d*)-(\d*)/', $_SERVER['HTTP_RANGE'], $matches)) {
            if ($matches[1] !== '') {
                $start = max(0, (int) $matches[1]);
            }
            if ($matches[2] !== '') {
                $end = min($end, (int) $matches[2]);
            }

            if ($start > $end || $start >= $fileSize) {
                http_response_code(416);
                header('Content-Range: bytes */' . $fileSize);
                exit;
            }

            http_response_code(206);
            header('Content-Range: bytes ' . $start . '-' . $end . '/' . $fileSize);
        }

        $length = $end - $start + 1;
        header('Content-Length: ' . $length);

        $handle = fopen($file['path'], 'rb');
        if ($handle === false) {
            http_response_code(500);
            exit('Could not open the file.');
        }

        fseek($handle, $start);
        $remaining = $length;
        while ($remaining > 0 && !feof($handle)) {
            $chunkSize = min(8192, $remaining);
            echo fread($handle, $chunkSize);
            $remaining -= $chunkSize;
            flush();
        }
        fclose($handle);
        exit;
    }

    private function sendPrivateLinkPlaceholder(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" viewBox="0 0 1920 1080" preserveAspectRatio="xMidYMid slice" role="img" aria-label="Private file">'
            . '<rect width="1920" height="1080" fill="#f4f7fb"/>'
            . '<path d="M0 780h1920v300H0z" fill="#eef3f9"/>'
            . '<path d="M280 184h1360" stroke="#d9e2ef" stroke-width="2" stroke-linecap="round" opacity=".75"/>'
            . '<path d="M280 896h1360" stroke="#d9e2ef" stroke-width="2" stroke-linecap="round" opacity=".75"/>'
            . '<g transform="translate(960 500)">'
            . '<rect x="-210" y="-260" width="420" height="360" rx="32" fill="#ffffff" stroke="#d6dfeb" stroke-width="3"/>'
            . '<path d="M-110 -62v-70c0-73 51-126 110-126s110 53 110 126v70H62v-70c0-44-26-78-62-78s-62 34-62 78v70z" fill="#1d4ed8"/>'
            . '<rect x="-145" y="-78" width="290" height="180" rx="28" fill="#e7edf7"/>'
            . '<circle cx="0" cy="-4" r="28" fill="#1d4ed8"/>'
            . '<rect x="-12" y="18" width="24" height="54" rx="12" fill="#1d4ed8"/>'
            . '<rect x="-145" y="-78" width="290" height="180" rx="28" fill="none" stroke="#dbe4f0" stroke-width="2"/>'
            . '<text x="0" y="185" text-anchor="middle" font-family="Arial, sans-serif" font-size="46" font-weight="700" fill="#0f172a">Private file</text>'
            . '<text x="0" y="235" text-anchor="middle" font-family="Arial, sans-serif" font-size="24" fill="#52657a">This public link is not available.</text>'
            . '</g>'
            . '</svg>';

        http_response_code(200);
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store, private');
        header('Content-Type: image/svg+xml; charset=UTF-8');
        header('Content-Disposition: inline; filename="private-file.svg"');
        header('Content-Length: ' . strlen($svg));
        echo $svg;
        exit;
    }

    private function canInline(array $file): bool
    {
        $mime = (string) ($file['mime_type'] ?? '');

        return $mime === 'application/pdf'
            || $mime === 'text/plain'
            || str_starts_with($mime, 'image/')
            || str_starts_with($mime, 'audio/')
            || str_starts_with($mime, 'video/');
    }

    private function configuredFileTypes(): array
    {
        global $config;

        $types = array_values(array_unique(array_map(
            fn (array $meta): string => (string) ($meta['type'] ?? 'file'),
            $config['upload']['allowed_mimes'] ?? []
        )));
        sort($types);

        return $types;
    }

    private function uploadAccept(): string
    {
        global $config;

        $mimes = array_keys($config['upload']['allowed_mimes'] ?? []);
        $extensions = array_map(
            fn (array $meta): string => '.' . ltrim((string) ($meta['extension'] ?? ''), '.'),
            $config['upload']['allowed_mimes'] ?? []
        );

        return implode(',', array_values(array_unique(array_filter(array_merge($mimes, $extensions)))));
    }

    private function uploadMaxSize(): int
    {
        global $config;

        return max(0, (int) ($config['upload']['max_size'] ?? 0));
    }

    private function expectsJson(): bool
    {
        return str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
            || strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
    }

    private function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($payload);
        exit;
    }
}
