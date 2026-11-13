<?php
/**
 * errors/403.php — shown by require_role() when access is denied.
 * Included from includes/auth.php, so helpers are already loaded.
 */
$pageTitle = 'Access denied';
require __DIR__ . '/../includes/header.php';
?>
<div class="text-center py-5">
    <h1 class="display-5">403</h1>
    <p class="lead">You do not have permission to view this page.</p>
    <a class="btn btn-primary" href="<?= BASE_URL ?>/dashboard.php">Back to dashboard</a>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

