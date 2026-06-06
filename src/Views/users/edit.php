<?php

use Src\Core\Csrf;
use Src\Core\Helpers;
?>
<div class="row g-4">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-body">
                <h1 class="h4 mb-3">Edit User</h1>
                <form method="post" action="/user_edit.php">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input class="form-control" name="name" value="<?= Helpers::e($item['name']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input class="form-control" type="email" name="email" value="<?= Helpers::e($item['email']) ?>" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="role">
                                <option value="user" <?= $item['role'] === 'user' ? 'selected' : '' ?>>User</option>
                                <option value="admin" <?= $item['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="active" <?= $item['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="locked" <?= $item['status'] === 'locked' ? 'selected' : '' ?>>Locked</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Storage Quota (MB)</label>
                        <input class="form-control" type="number" name="storage_limit_mb" min="0" value="<?= (int) round(((int) $item['storage_limit']) / 1024 / 1024) ?>" required>
                        <div class="form-text">
                            Used <?= Helpers::e(Helpers::formatBytes((int) $storageUsed)) ?>.
                            Admin accounts are not limited by quota.
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary" type="submit">Save</button>
                        <a class="btn btn-outline-secondary" href="/users.php">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card">
            <div class="card-body">
                <h2 class="h5 mb-3">Change Password</h2>
                <form method="post" action="/user_password.php">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input class="form-control" type="password" name="password" minlength="6" required>
                    </div>
                    <button class="btn btn-outline-primary" type="submit">Update Password</button>
                </form>
            </div>
        </div>
    </div>
</div>
