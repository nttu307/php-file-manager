<?php

namespace Src\Controllers;

use Src\Core\Auth;
use Src\Core\Csrf;
use Src\Core\Helpers;
use Src\Core\View;
use Src\Models\ActivityLog;
use Src\Models\UserModel;

class ProfileController
{
    public function edit(): void
    {
        Auth::requireLogin();
        View::render('profile/edit');
    }

    public function updatePassword(): void
    {
        Auth::requireLogin();
        Csrf::verify();

        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $user = Auth::user();
        $fullUser = UserModel::findActiveByEmail($user['email']);

        if (!$fullUser || !password_verify($currentPassword, $fullUser['password_hash'])) {
            Helpers::flash('danger', 'Current password is incorrect.');
            Helpers::redirect('/profile.php');
        }

        if (strlen($newPassword) < 6 || $newPassword !== $confirmPassword) {
            Helpers::flash('danger', 'New password must be at least 6 characters and match the confirmation.');
            Helpers::redirect('/profile.php');
        }

        UserModel::updatePassword((int) $user['id'], $newPassword);
        ActivityLog::create('profile_password_update');
        Helpers::flash('success', 'Password updated successfully.');
        Helpers::redirect('/profile.php');
    }
}
