<?php

namespace Src\Controllers;

use Src\Core\Auth;
use Src\Core\Csrf;
use Src\Core\Helpers;
use Src\Core\View;

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

        if (Auth::attempt($email, $password)) {
            Helpers::redirect('/files.php');
        }

        Helpers::flash('danger', 'Invalid email or password.');
        Helpers::redirect('/login.php');
    }

    public function logout(): void
    {
        Auth::logout();
        Helpers::redirect('/login.php');
    }
}
