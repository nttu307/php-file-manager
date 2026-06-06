<?php

use Src\Core\Csrf;
?>
<div class="row justify-content-center">
    <div class="col-md-5 col-lg-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h1 class="h4 mb-3">Sign In</h1>
                <form method="post">
                    <?= Csrf::field() ?>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input class="form-control" type="email" name="email" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input class="form-control" type="password" name="password" required>
                    </div>
                    <button class="btn btn-primary w-100" type="submit">Sign In</button>
                </form>
            </div>
        </div>
    </div>
</div>
