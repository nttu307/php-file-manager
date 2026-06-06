<?php

use Src\Core\Csrf;
?>
<div class="row justify-content-center">
    <div class="col-md-7 col-lg-6">
        <div class="card">
            <div class="card-body">
                <h1 class="h4 mb-3">Create User</h1>
                <form method="post">
                    <?= Csrf::field() ?>
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input class="form-control" type="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input class="form-control" type="password" name="password" minlength="6" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select class="form-select" name="role">
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Storage Quota (MB)</label>
                        <input class="form-control" type="number" name="storage_limit_mb" min="0" value="500" required>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary" type="submit">Create</button>
                        <a class="btn btn-outline-secondary" href="/users.php">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
