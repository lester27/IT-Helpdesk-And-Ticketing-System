<?php
/**
 * includes/navbar.php — role-aware navigation
 * OWNER: Miguel
 *
 * Menu by role:
 *   Employee   : Dashboard, My Tickets, New Ticket
 *   Technician : Dashboard, Assigned Tickets, All Tickets, Equipment (view)
 *   Admin      : Dashboard, Tickets, Equipment, Users, Departments, Reports
 */
$u = current_user();
if (!function_exists('nav_active')) {
    function nav_active(string $target, array $except = []): string {
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
        if (substr($target, -1) === '/') {
            $match = basename(dirname($script)) === trim($target, '/')
                  && !in_array(basename($script), $except, true);
        } else {
            $match = substr($script, -(strlen($target) + 1)) === '/' . $target;
        }
        return $match ? ' active' : '';
    }
}
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="<?= BASE_URL ?>/dashboard.php"><i class="bi bi-life-preserver"></i> IT Helpdesk</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link<?= nav_active('dashboard.php') ?>" href="<?= BASE_URL ?>/dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link<?= nav_active('tickets/', ['create.php']) ?>" href="<?= BASE_URL ?>/tickets/index.php">Tickets</a></li>

                <?php if (has_role('Employee')): ?>
                <li class="nav-item"><a class="nav-link<?= nav_active('tickets/create.php') ?>" href="<?= BASE_URL ?>/tickets/create.php">New Ticket</a></li>
                    <?php endif; ?>

                <?php if (has_role('Technician', 'Admin')): ?>
                <li class="nav-item"><a class="nav-link<?= nav_active('equipment/') ?>" href="<?= BASE_URL ?>/equipment/index.php">Equipment</a></li>
                    <?php endif; ?>

                <?php if (has_role('Admin')): ?>
                <li class="nav-item"><a class="nav-link<?= nav_active('users/') ?>" href="<?= BASE_URL ?>/users/index.php">Users</a></li>
                <li class="nav-item"><a class="nav-link<?= nav_active('departments/') ?>" href="<?= BASE_URL ?>/departments/index.php">Departments</a></li>
                <li class="nav-item"><a class="nav-link<?= nav_active('reports/') ?>" href="<?= BASE_URL ?>/reports/index.php">Reports</a></li>
                <?php endif; ?>
            </ul>

            <span class="navbar-text me-3">
                <?= e($u['full_name']) ?> <span class="badge text-bg-secondary"><?= e($u['role']) ?></span>
            </span>
            <a class="btn btn-outline-light btn-sm" href="<?= BASE_URL ?>/logout.php">Logout</a>
        </div>
    </div>
</nav>

