<?php

use Src\Core\Auth;
use Src\Core\Csrf;
use Src\Core\Helpers;

$user = Auth::user();
$initials = strtoupper(substr($user['name'] ?: $user['email'], 0, 1));
?>
<div class="profile-grid">
    <section class="card profile-summary-card">
        <div class="card-body">
            <div class="profile-identity">
                <div class="profile-avatar"><?= Helpers::e($initials) ?></div>
                <div>
                    <h1 class="h4 mb-1">My Account</h1>
                    <div class="text-muted">Manage your account information and password.</div>
                </div>
            </div>

            <div class="profile-detail-list">
                <div class="profile-detail-item">
                    <div class="profile-detail-icon"><i class="bi bi-person"></i></div>
                    <div>
                        <div class="text-muted small">Name</div>
                        <div class="fw-semibold"><?= Helpers::e($user['name']) ?></div>
                    </div>
                </div>
                <div class="profile-detail-item">
                    <div class="profile-detail-icon"><i class="bi bi-envelope"></i></div>
                    <div>
                        <div class="text-muted small">Email</div>
                        <div class="fw-semibold"><?= Helpers::e($user['email']) ?></div>
                    </div>
                </div>
                <div class="profile-detail-item">
                    <div class="profile-detail-icon"><i class="bi bi-shield-lock"></i></div>
                    <div>
                        <div class="text-muted small">Role</div>
                        <span class="badge <?= $user['role'] === 'admin' ? 'text-bg-primary' : 'text-bg-secondary' ?>">
                            <?= Helpers::e($user['role']) ?>
                        </span>
                    </div>
                </div>
                <div class="profile-detail-item">
                    <div class="profile-detail-icon"><i class="bi bi-check-circle"></i></div>
                    <div>
                        <div class="text-muted small">Status</div>
                        <span class="badge <?= $user['status'] === 'active' ? 'text-bg-success' : 'text-bg-danger' ?>">
                            <?= Helpers::e($user['status']) ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="card">
        <div class="card-body">
            <div class="d-flex align-items-center gap-2 mb-3">
                <div class="profile-section-icon"><i class="bi bi-key"></i></div>
                <div>
                    <h2 class="h5 mb-0">Change Password</h2>
                    <div class="text-muted small">Use a strong password with at least 6 characters.</div>
                </div>
            </div>

            <form method="post">
                <?= Csrf::field() ?>
                <div class="mb-3">
                    <label class="form-label">Current Password</label>
                    <input class="form-control" type="password" name="current_password" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">New Password</label>
                    <input class="form-control" type="password" name="new_password" minlength="6" required>
                </div>
                <div class="mb-4">
                    <label class="form-label">Confirm New Password</label>
                    <input class="form-control" type="password" name="confirm_password" minlength="6" required>
                </div>
                <button class="btn btn-primary" type="submit">
                    <i class="bi bi-check2-circle"></i>
                    <span>Update Password</span>
                </button>
            </form>
        </div>
    </section>
</div>
