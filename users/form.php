<?php
/**
 * users/form.php[?id=N] — create or edit a user (Admin)
 * OWNER: Harold
 *
 * Fields: full_name, username (UNIQUE), email (UNIQUE, validate format),
 *         department_id (select), role (ROLES — BR1), password
 *         (required on create; on edit, blank = keep current)
 *
 * Rules:
 *   - Store password_hash($password, PASSWORD_DEFAULT) — never plain text
 *   - Duplicate username/email -> catch PDOException 23000, friendly error (BR3)
 *   - Changing a Technician to another role while they have unresolved tickets -> block or warn
 */
require_once __DIR__ . '/../includes/auth.php';
require_role(['Admin']);

$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;
$isEdit = $id !== null;

// TODO(Users): load departments; load user if editing; handle POST

$pageTitle = $isEdit ? 'Edit User' : 'Add User';
require __DIR__ . '/../includes/header.php';
?>

<h1 class="h3 mb-3"><?= e($pageTitle) ?></h1>

<!-- ===== USER FORM ===== -->
<form method="post" class="card card-body">
    <?= csrf_field() ?>
    <!-- TODO(Users): inputs -->
</form>

<?php require __DIR__ . '/../includes/footer.php'; ?>

