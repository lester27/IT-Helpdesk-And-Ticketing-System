<?php
/**
 * login.php — login form
 * OWNER: Lauren (page design) · Harold (login logic in includes/auth.php)
 *
 * GET  : show form
 * POST : verify_csrf(), attempt_login($username, $password)
 *        success -> redirect to dashboard.php
 *        failure -> flash('danger', 'Invalid username or password.')  (same message for wrong user / wrong pw / inactive)
 */
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    redirect(BASE_URL . '/dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (attempt_login(trim($_POST['username'] ?? ''), $_POST['password'] ?? '')) {
        redirect(BASE_URL . '/dashboard.php');
    }
    flash('danger', 'Invalid username or password.');
    redirect(BASE_URL . '/login.php');
}

$pageTitle = 'Login';
require __DIR__ . '/includes/header.php';
?>

<!-- ===== LOGIN CARD (Bootstrap card, centered) ===== -->
<div class="row justify-content-center">
    <div class="col-md-5 col-lg-4">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h4 mb-3 text-center">IT Helpdesk Login</h1>

                <form method="post">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label" for="username">Username</label>
                        <input class="form-control" id="username" name="username" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password">Password</label>
                        <input class="form-control" id="password" name="password" type="password" required>
                    </div>
                    <button class="btn btn-primary w-100" type="submit">Sign in</button>
                    <!-- TODO(Auth): polish (logo, "show password", etc.) -->
                </form>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
