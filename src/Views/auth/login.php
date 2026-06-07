<?php

use Src\Core\Csrf;
?>
<div class="login-page">
    <div class="login-card">
        <div class="login-panel">
            <div class="login-brand-icon">
                <i class="bi bi-folder2-open"></i>
            </div>
            <div>
                <div class="login-kicker">Secure workspace</div>
                <h1>File Manager</h1>
                <p>Access your library, manage shared links, and keep uploaded files organized.</p>
            </div>
            <div class="login-panel-footer">
                <span><i class="bi bi-shield-check"></i> Protected access</span>
                <span><i class="bi bi-cloud-arrow-up"></i> File uploads</span>
            </div>
        </div>
        <div class="login-form-panel">
            <div class="login-form-header">
                <h2>Sign in</h2>
                <p>Use your account credentials to continue.</p>
            </div>
            <form method="post">
                <?= Csrf::field() ?>
                <div class="mb-3">
                    <label class="form-label">Email address</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input class="form-control" type="email" name="email" autocomplete="email" required autofocus>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-key"></i></span>
                        <input class="form-control" type="password" name="password" autocomplete="current-password" required>
                    </div>
                </div>
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="form-check">
                        <input id="remember" class="form-check-input" type="checkbox" name="remember" value="1">
                        <label class="form-check-label small" for="remember">Remember me</label>
                    </div>
                    <a class="small text-decoration-none" href="/forgot_password.php">Forgot password?</a>
                </div>
                <button class="btn btn-primary w-100 login-submit" type="submit">
                    <i class="bi bi-box-arrow-in-right"></i>
                    <span>Sign in</span>
                </button>
            </form>
        </div>
    </div>
</div>
