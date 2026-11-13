<?php
/**
 * users/index.php — user management list (Admin)
 * OWNER: Harold
 *
 * Columns: full_name, username, email, department, role badge, active/inactive, created_at
 * Filters: role, department, active
 * Actions: Add user -> form.php, Edit -> form.php?id=N,
 *          Skills -> skills.php?id=N (Technicians only),
 *          Activate / Deactivate toggle (POST) — never DELETE (BR4)
 */
require_once __DIR__ . '/../includes/auth.php';
require_role(['Admin']);

// TODO(Users): handle POST toggle is_active (cannot deactivate yourself)
// TODO(Users): query users JOIN departments + filters
$users = [];

$pageTitle = 'Users';
require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Users</h1>
    <a class="btn btn-primary" href="form.php"><i class="bi bi-person-plus"></i> Add User</a>
</div>

<!-- ===== FILTER BAR ===== -->
<!-- ===== USERS TABLE ===== -->

<?php require __DIR__ . '/../includes/footer.php'; ?>

