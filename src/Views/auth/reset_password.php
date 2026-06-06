<?php

use Src\Core\Csrf;
use Src\Core\Helpers;
?>
<div class="auth-simple-page">
    <div class="auth-simple-card">
        <div class="auth-simple-icon">
            <i class="bi bi-key"></i>
        </div>
        <h1>Create new password</h1>
        <p>Set a new password for <?= Helpers::e($email ?? 'your account') ?>.</p>
        <form method="post">
            <?= Csrf::field() ?>
            <input type="hidden" name="token" value="<?= Helpers::e($token ?? '') ?>">
            <div class="mb-3">
                <label class="form-label">New password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-key"></i></span>
                    <input class="form-control" type="password" name="password" autocomplete="new-password" required autofocus>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label">Confirm new password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-check2-square"></i></span>
                    <input class="form-control" type="password" name="password_confirm" autocomplete="new-password" required>
                </div>
            </div>
            <button class="btn btn-primary w-100 login-submit" type="submit">
                <i class="bi bi-shield-check"></i>
                <span>Reset password</span>
            </button>
        </form>
    </div>
</div>
