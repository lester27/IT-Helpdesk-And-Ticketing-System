<?php
/**
 * errors/403.php — shown by require_role() when access is denied.
 * Included from includes/auth.php, so helpers are already loaded.
 */
http_response_code(403);
$pageTitle = 'Access denied';
require __DIR__ . '/../includes/header.php';
?>
<div class="text-center py-5">
    <i class="bi bi-shield-lock display-1 text-danger"></i>
    <h1 class="display-5 mt-3">403 · Access denied</h1>
    <p class="lead text-muted">You do not have permission to view this page.</p>
    <a class="btn btn-primary" href="<?= BASE_URL ?>/dashboard.php">
        <i class="bi bi-arrow-left"></i> Back to dashboard
    </a>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>