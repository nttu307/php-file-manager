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
use Src\Services\StorageService;

class FileController
{
    public function index(): void
    {
        Auth::requireLogin();
        FileModel::purgeExpiredTrash();

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 12;
        $filters = [
            'q' => trim($_GET['q'] ?? ''),
            'user_id' => (int) ($_GET['user_id'] ?? 0),
            'from' => trim($_GET['from'] ?? ''),
            'to' => trim($_GET['to'] ?? ''),
        ];
        $result = FileModel::paginate($page, $perPage, $filters);
        $user = Auth::user();

        View::render('files/index', [
            'files' => $result['items'],
            'total' => $result['total'],
            'page' => $page,
            'totalPages' => max(1, (int) ceil($result['total'] / $perPage)),
            'filters' => $filters,
            'users' => Auth::isAdmin() ? UserModel::all() : [],
            'storageUsed' => Auth::isAdmin() ? FileModel::totalStorageUsed() : ($user ? UserModel::storageUsed((int) $user['id']) : 0),
        ]);
    }

    public function upload(): void
    {
        Auth::requireLogin();
        Csrf::verify();

        try {
            $created = FileModel::createManyFromUpload($_FILES['images'] ?? []);
            Helpers::flash('success', 'Uploaded ' . count($created) . ' file(s).');
        } catch (Throwable $e) {
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

        if (!isset($_GET['token']) || $file['visibility'] !== 'public') {
            Auth::requireLogin();
            Auth::requireFilePermission($file);
        }

        $this->sendFile($file, false);
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

        Auth::requireFilePermission($file);
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
            Helpers::flash('danger', 'File not found.');
            Helpers::redirect('/files.php');
        }

        Auth::requireFilePermission($file);
        $visibility = ($_POST['visibility'] ?? '') === 'public' ? 'public' : 'private';
        FileModel::updateVisibility($file, $visibility);

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

        View::render('files/trash', [
            'files' => $result['items'],
            'total' => $result['total'],
            'page' => $page,
            'totalPages' => max(1, (int) ceil($result['total'] / $perPage)),
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

        Auth::requireFilePermission($file);
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

        Auth::requireFilePermission($file);
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
        $this->sendZip([$file], pathinfo(StorageService::safeDownloadName($file['original_name'], 'image'), PATHINFO_FILENAME) . '.zip');
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

        $this->sendZip($files, 'selected-images.zip');
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
                $zip->addFile($file['path'], StorageService::safeDownloadName($file['original_name'], 'image'));
            }
        }
        $zip->close();

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . StorageService::safeDownloadName($downloadName, 'images.zip') . '"');
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

        header('Content-Type: ' . $file['mime_type']);
        header('Content-Length: ' . filesize($file['path']));
        header('Content-Disposition: ' . ($download ? 'attachment' : 'inline') . '; filename="' . StorageService::safeDownloadName($file['original_name'], 'image') . '"');
        readfile($file['path']);
        exit;
    }
}
