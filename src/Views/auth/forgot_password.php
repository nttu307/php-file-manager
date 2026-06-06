<?php

use Src\Core\Csrf;
?>
<div class="auth-simple-page">
    <div class="auth-simple-card">
        <div class="auth-simple-icon">
            <i class="bi bi-envelope-check"></i>
        </div>
        <h1>Reset password</h1>
        <p>Enter your account email. If it exists, a reset link will be sent to that inbox.</p>
        <form method="post">
            <?= Csrf::field() ?>
            <div class="mb-3">
                <label class="form-label">Email address</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input class="form-control" type="email" name="email" autocomplete="email" required autofocus>
                </div>
            </div>
            <button class="btn btn-primary w-100 login-submit" type="submit">
                <i class="bi bi-send"></i>
                <span>Send reset link</span>
            </button>
            <a class="btn btn-link w-100 mt-3" href="/login.php">Back to sign in</a>
        </form>
    </div>
</div>
