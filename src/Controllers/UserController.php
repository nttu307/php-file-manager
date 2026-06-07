<?php

namespace Src\Controllers;

use Throwable;
use Src\Core\Auth;
use Src\Core\Csrf;
use Src\Core\Helpers;
use Src\Core\View;
use Src\Models\ActivityLog;
use Src\Models\UserModel;
use Src\Services\StorageService;

class UserController
{
    public function index(): void
    {
        Auth::requireAdmin();
        View::render('users/index', ['users' => UserModel::all()]);
    }

    public function create(): void
    {
        Auth::requireAdmin();
        View::render('users/create', [
            'assignableQuota' => $this->assignableQuotaBytes(),
        ]);
    }

    public function store(): void
    {
        Auth::requireAdmin();
        Csrf::verify();

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = ($_POST['role'] ?? '') === 'admin' ? 'admin' : 'user';
        $storageLimit = $role === 'admin' ? null : max(1, (int) ($_POST['storage_limit_mb'] ?? 500)) * 1024 * 1024;

        if ($name === '' || $email === '' || strlen($password) < 6) {
            Helpers::flash('danger', 'Please fill in all fields. Password must be at least 6 characters.');
            Helpers::redirect('/user_create.php');
        }

        if ($storageLimit !== null && $storageLimit > $this->assignableQuotaBytes()) {
            Helpers::flash('danger', 'Storage quota exceeds the currently assignable storage.');
            Helpers::redirect('/user_create.php');
        }

        try {
            UserModel::create($name, $email, $password, $role, $storageLimit);
            ActivityLog::create('user_create');
            Helpers::flash('success', 'User created successfully.');
            Helpers::redirect('/users.php');
        } catch (Throwable $e) {
            Helpers::flash('danger', 'Could not create user. The email may already exist.');
            Helpers::redirect('/user_create.php');
        }
    }

    public function edit(): void
    {
        Auth::requireAdmin();

        $user = UserModel::findById((int) ($_GET['id'] ?? 0));
        if (!$user) {
            http_response_code(404);
            exit('User not found.');
        }

        View::render('users/edit', [
            'item' => $user,
            'storageUsed' => UserModel::storageUsed((int) $user['id']),
            'assignableQuota' => $this->assignableQuotaBytes($user),
        ]);
    }

    public function update(): void
    {
        Auth::requireAdmin();
        Csrf::verify();

        $id = (int) ($_POST['id'] ?? 0);
        $user = UserModel::findById($id);
        if (!$user) {
            http_response_code(404);
            exit('User not found.');
        }

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = ($_POST['role'] ?? '') === 'admin' ? 'admin' : 'user';
        $status = ($_POST['status'] ?? '') === 'locked' ? 'locked' : 'active';
        $storageLimit = $role === 'admin' ? null : max(1, (int) ($_POST['storage_limit_mb'] ?? 500)) * 1024 * 1024;

        if ($id === (int) Auth::user()['id'] && $status === 'locked') {
            Helpers::flash('danger', 'You cannot lock the account currently in use.');
            Helpers::redirect('/user_edit.php?id=' . $id);
        }

        if ($name === '' || $email === '') {
            Helpers::flash('danger', 'Please enter a name and email.');
            Helpers::redirect('/user_edit.php?id=' . $id);
        }

        if ($storageLimit !== null && $storageLimit > $this->assignableQuotaBytes($user)) {
            Helpers::flash('danger', 'Storage quota exceeds the currently assignable storage.');
            Helpers::redirect('/user_edit.php?id=' . $id);
        }

        try {
            UserModel::update($id, $name, $email, $role, $status, $storageLimit);
            ActivityLog::create('user_update');
            Helpers::flash('success', 'User updated successfully.');
            Helpers::redirect('/users.php');
        } catch (Throwable $e) {
            Helpers::flash('danger', 'Could not update user. The email may already exist.');
            Helpers::redirect('/user_edit.php?id=' . $id);
        }
    }

    public function password(): void
    {
        Auth::requireAdmin();
        Csrf::verify();

        $id = (int) ($_POST['id'] ?? 0);
        $password = $_POST['password'] ?? '';

        if (!UserModel::findById($id)) {
            http_response_code(404);
            exit('User not found.');
        }

        if (strlen($password) < 6) {
            Helpers::flash('danger', 'Password must be at least 6 characters.');
            Helpers::redirect('/user_edit.php?id=' . $id);
        }

        UserModel::updatePassword($id, $password);
        ActivityLog::create('user_password_update');
        Helpers::flash('success', 'User password updated successfully.');
        Helpers::redirect('/user_edit.php?id=' . $id);
    }

    private function assignableQuotaBytes(array $currentUser = null): int
    {
        $currentLimit = 0;
        if ($currentUser && ($currentUser['role'] ?? '') !== 'admin') {
            $currentLimit = (int) ($currentUser['storage_limit'] ?? 0);
        }

        return StorageService::diskStats()['usable'] + $currentLimit;
    }
}
