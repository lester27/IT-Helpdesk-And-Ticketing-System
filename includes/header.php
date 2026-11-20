<?php
/**
 * includes/header.php — page <head>, Bootstrap, navbar, flash messages
 * OWNER: Miguel
 *
 * Usage:
 *     $pageTitle = 'Tickets';
 *     require __DIR__ . '/../includes/header.php';
 *     ... page content ...
 *     require __DIR__ . '/../includes/footer.php';
 */
$pageTitle = $pageTitle ?? 'IT Helpdesk';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> · IT Helpdesk</title>

    <!-- Bootstrap 5 (CSS) — the ONLY styling framework for this project -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Small overrides only. Prefer Bootstrap utility classes. -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body class="bg-light d-flex flex-column min-vh-100">

<?php if (is_logged_in()) require __DIR__ . '/navbar.php'; ?>

<main class="container py-4 flex-grow-1">
    <?php foreach (get_flashes() as $f): ?>
        <div class="alert alert-<?= e($f['type']) ?> alert-dismissible fade show" role="alert">
            <?= e($f['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endforeach; ?>

