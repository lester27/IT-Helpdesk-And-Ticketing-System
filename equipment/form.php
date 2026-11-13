<?php
/**
 * equipment/form.php[?id=N] — create or edit equipment (Admin)
 * OWNER: Harold
 *
 * Fields: equipment_type, brand, model, serial_number (UNIQUE — BR12),
 *         status (EQUIPMENT_STATUSES), warranty_expiry (optional date),
 *         assigned_user_id (optional select of ACTIVE users)
 *
 * Rules:
 *   - Duplicate serial -> catch PDOException code 23000, show friendly error
 *   - BR19: if status = 'Retired', assigned_user_id must be NULL
 *           (cannot give retired equipment to a user)
 *   - Keep status consistent: holder set -> 'Assigned'; holder cleared -> suggest 'In Stock'
 *   - NO delete button (ON DELETE RESTRICT; retire instead)
 */
require_once __DIR__ . '/../includes/auth.php';
require_role(['Admin']);

$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;
$isEdit = $id !== null;

// TODO(Equipment): load row if editing; load active users for holder select
// TODO(Equipment): handle POST (INSERT or UPDATE)

$pageTitle = $isEdit ? 'Edit Equipment' : 'Add Equipment';
require __DIR__ . '/../includes/header.php';
?>

<h1 class="h3 mb-3"><?= e($pageTitle) ?></h1>

<!-- ===== EQUIPMENT FORM ===== -->
<form method="post" class="card card-body">
    <?= csrf_field() ?>
    <!-- TODO(Equipment): inputs -->
</form>

<?php require __DIR__ . '/../includes/footer.php'; ?>

