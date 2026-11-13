<?php
/**
 * departments/index.php — list + add + rename departments (Admin)
 * OWNER: Miguel
 *
 * Single page: table of departments with user count, inline "Add" form,
 * rename via small form/modal.
 * Rules: department_name UNIQUE; no delete (users reference it — ON DELETE RESTRICT).
 */
require_once __DIR__ . '/../includes/auth.php';
require_role(['Admin']);

// TODO(Users): handle POST add / rename
// TODO(Users): SELECT d.*, COUNT(u.user_id) FROM departments d LEFT JOIN users u ... GROUP BY
$departments = [];

$pageTitle = 'Departments';
require __DIR__ . '/../includes/header.php';
?>

<h1 class="h3 mb-3">Departments</h1>

<!-- ===== ADD DEPARTMENT FORM ===== -->
<!-- ===== DEPARTMENTS TABLE ===== -->

<?php require __DIR__ . '/../includes/footer.php'; ?>

