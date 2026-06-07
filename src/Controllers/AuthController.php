<?php

namespace Src\Controllers;

use Throwable;
use Src\Core\Auth;
use Src\Core\Csrf;
use Src\Core\Helpers;
use Src\Core\View;
use Src\Models\ActivityLog;
use Src\Models\PasswordResetModel;
use Src\Models\UserModel;
use Src\Services\MailService;

class AuthController
{
    public function showLogin(): void
    {
        if (Auth::user()) {
            Helpers::redirect('/files.php');
        }

        View::render('auth/login');
    }

    public function login(): void
    {
        Csrf::verify();

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = ($_POST['remember'] ?? '') === '1';

        if (Auth::attempt($email, $password, $remember)) {
            ActivityLog::create('login');
            Helpers::redirect('/files.php');
        }

        Helpers::flash('danger', 'Invalid email or password.');
        Helpers::redirect('/login.php');
    }

    public function showForgotPassword(): void
    {
        if (Auth::user()) {
            Helpers::redirect('/files.php');
        }

        View::render('auth/forgot_password');
    }

    public function sendResetLink(): void
    {
        global $config;

        Csrf::verify();

        $email = trim($_POST['email'] ?? '');
        $user = UserModel::findActiveByEmail($email);
        if (!$user) {
            Helpers::flash('danger', 'No active account was found for that email address.');
            Helpers::redirect('/forgot_password.php');
        }

        $token = PasswordResetModel::create((int) $user['id'], (int) $config['mail']['password_reset_minutes']);
        $resetUrl = Helpers::appUrl('/reset_password.php?token=' . $token);
        try {
            MailService::sendPasswordReset($user['email'], $user['name'], $resetUrl);
        } catch (Throwable $e) {
            Helpers::flash('danger', 'Could not send reset email: ' . $e->getMessage());
            Helpers::redirect('/forgot_password.php');
        }

        Helpers::flash('success', 'A password reset link has been sent to your email.');
        Helpers::redirect('/login.php');
    }

    public function showResetPassword(): void
    {
        if (Auth::user()) {
            Helpers::redirect('/files.php');
        }

        $token = trim((string) ($_GET['token'] ?? ''));
        $reset = PasswordResetModel::findValidByToken($token);
        if (!$reset) {
            Helpers::flash('danger', 'This password reset link is invalid or has expired.');
            Helpers::redirect('/forgot_password.php');
        }

        View::render('auth/reset_password', [
            'token' => $token,
            'email' => $reset['user_email'],
        ]);
    }

    public function resetPassword(): void
    {
        Csrf::verify();

        $token = trim((string) ($_POST['token'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $confirmPassword = (string) ($_POST['password_confirm'] ?? '');
        $reset = PasswordResetModel::findValidByToken($token);

        if (!$reset) {
            Helpers::flash('danger', 'This password reset link is invalid or has expired.');
            Helpers::redirect('/forgot_password.php');
        }

        if (strlen($password) < 6) {
            Helpers::flash('danger', 'Password must be at least 6 characters.');
            Helpers::redirect('/reset_password.php?token=' . urlencode($token));
        }

        if ($password !== $confirmPassword) {
            Helpers::flash('danger', 'Password confirmation does not match.');
            Helpers::redirect('/reset_password.php?token=' . urlencode($token));
        }

        UserModel::updatePassword((int) $reset['user_id'], $password);
        PasswordResetModel::markUsed((int) $reset['id']);
        Helpers::flash('success', 'Password has been reset. You can now sign in.');
        Helpers::redirect('/login.php');
    }

    public function logout(): void
    {
        if (Auth::user()) {
            ActivityLog::create('logout');
        }
        Auth::logout();
        Helpers::redirect('/login.php');
    }
}
