<?php
/**
 * equipment/form.php[?id=N] — create or edit equipment (Admin)
 * OWNER: Harold
 *
 * Fields: equipment_type, brand, model, serial_number (UNIQUE — BR13),
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
$errors = [];

// Load active users for the holder dropdown (BR4: inactive users cannot hold equipment)
$activeUsers = db()->query(
    'SELECT u.user_id, u.full_name, u.username, d.department_name
     FROM users u
     JOIN departments d ON u.department_id = d.department_id
     WHERE u.is_active = 1
     ORDER BY u.full_name ASC'
)->fetchAll();

$activeUserIds = array_map('intval', array_column($activeUsers, 'user_id'));

// Default form values
$equipment = [
    'equipment_type'   => '',
    'brand'            => '',
    'model'            => '',
    'serial_number'    => '',
    'status'           => 'In Stock',
    'warranty_expiry'  => '',
    'assigned_user_id' => '',
];

if ($isEdit) {
    $stmt = db()->prepare(
        'SELECT equipment_id, equipment_type, brand, model, serial_number, status, warranty_expiry, assigned_user_id
         FROM equipment
         WHERE equipment_id = ?'
    );
    $stmt->execute([$id]);
    $existing = $stmt->fetch();

    if (!$existing) {
        flash('danger', 'Equipment record not found.');
        redirect(BASE_URL . '/equipment/index.php');
    }

    $equipment = $existing;
}

// ---------------------------------------------------------------------
// HANDLE FORM SUBMISSION
// ---------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $equipmentType  = trim($_POST['equipment_type'] ?? '');
    $brand          = trim($_POST['brand'] ?? '');
    $model          = trim($_POST['model'] ?? '');
    $serialNumber   = trim($_POST['serial_number'] ?? '');
    $status         = trim($_POST['status'] ?? '');
    $warrantyExpiry = trim($_POST['warranty_expiry'] ?? '');
    $assignedRaw    = trim($_POST['assigned_user_id'] ?? '');
    $assignedUserId = $assignedRaw !== '' ? (int)$assignedRaw : null;

    // Repopulate form values on validation error
    $equipment['equipment_type']   = $equipmentType;
    $equipment['brand']            = $brand;
    $equipment['model']            = $model;
    $equipment['serial_number']    = $serialNumber;
    $equipment['status']           = $status;
    $equipment['warranty_expiry']  = $warrantyExpiry;
    $equipment['assigned_user_id'] = $assignedUserId ?? '';

    // Required field validations
    if ($equipmentType === '') {
        $errors[] = 'Equipment type is required (e.g., Laptop, Printer, Monitor).';
    } elseif (strlen($equipmentType) > 50) {
        $errors[] = 'Equipment type must be 50 characters or fewer.';
    }

    if ($brand === '') {
        $errors[] = 'Brand is required.';
    } elseif (strlen($brand) > 50) {
        $errors[] = 'Brand must be 50 characters or fewer.';
    }

    if ($model === '') {
        $errors[] = 'Model is required.';
    } elseif (strlen($model) > 50) {
        $errors[] = 'Model must be 50 characters or fewer.';
    }

    if ($serialNumber === '') {
        $errors[] = 'Serial number is required.';
    } elseif (strlen($serialNumber) > 50) {
        $errors[] = 'Serial number must be 50 characters or fewer.';
    }

    if (!in_array($status, EQUIPMENT_STATUSES, true)) {
        $errors[] = 'Please select a valid equipment status.';
    }

    // Optional warranty expiry date validation
    $warrantyDbValue = null;
    if ($warrantyExpiry !== '') {
        $dt = DateTime::createFromFormat('Y-m-d', $warrantyExpiry);
        if (!$dt || $dt->format('Y-m-d') !== $warrantyExpiry) {
            $errors[] = 'Warranty expiry must be a valid date (YYYY-MM-DD).';
        } else {
            $warrantyDbValue = $warrantyExpiry;
        }
    }

    // Holder must be an active user (BR4)
    if ($assignedUserId !== null && !in_array($assignedUserId, $activeUserIds, true)) {
        $errors[] = 'Assigned holder must be an active user.';
    }

    // BR19: Retired equipment cannot have a holder
    if ($status === 'Retired' && $assignedUserId !== null) {
        $errors[] = 'Retired equipment cannot be assigned to a user (BR19). Clear the assigned holder or choose another status.';
    }

    // BR14: Holder <-> Status consistency
    if ($status === 'Assigned' && $assignedUserId === null) {
        $errors[] = 'Status is "Assigned", so an active user must be selected as the holder (BR14). Otherwise, set status to "In Stock".';
    } elseif ($status === 'In Stock' && $assignedUserId !== null) {
        $errors[] = 'Equipment with an assigned holder cannot have status "In Stock" (BR14). Set status to "Assigned" or clear the holder.';
    }

    // Save if valid
    if (empty($errors)) {
        try {
            if ($isEdit) {
                $stmt = db()->prepare(
                    'UPDATE equipment
                     SET equipment_type = ?, brand = ?, model = ?, serial_number = ?,
                         status = ?, warranty_expiry = ?, assigned_user_id = ?
                     WHERE equipment_id = ?'
                );
                $stmt->execute([
                    $equipmentType,
                    $brand,
                    $model,
                    $serialNumber,
                    $status,
                    $warrantyDbValue,
                    $assignedUserId,
                    $id,
                ]);

                flash('success', 'Equipment "' . e($brand . ' ' . $model) . '" (' . e($serialNumber) . ') was updated.');
                redirect(BASE_URL . '/equipment/index.php');
            } else {
                $stmt = db()->prepare(
                    'INSERT INTO equipment (equipment_type, brand, model, serial_number, status, warranty_expiry, assigned_user_id)
                     VALUES (?, ?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([
                    $equipmentType,
                    $brand,
                    $model,
                    $serialNumber,
                    $status,
                    $warrantyDbValue,
                    $assignedUserId,
                ]);

                flash('success', 'Equipment "' . e($brand . ' ' . $model) . '" (' . e($serialNumber) . ') was added.');
                redirect(BASE_URL . '/equipment/index.php');
            }
        } catch (PDOException $e) {
            // BR13: Catch duplicate serial_number
            if ($e->getCode() === '23000' || (isset($e->errorInfo[1]) && $e->errorInfo[1] === 1062)) {
                $errors[] = 'The serial number "' . e($serialNumber) . '" is already registered. Each piece of equipment must have a unique serial number.';
            } else {
                $errors[] = 'An unexpected database error occurred: ' . e($e->getMessage());
            }
        }
    }
}

$pageTitle = $isEdit ? 'Edit Equipment' : 'Add Equipment';
require __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-7 col-md-9">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h3 mb-0"><?= e($pageTitle) ?></h1>
            <a href="index.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Back to Equipment
            </a>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger shadow-sm">
                <div class="fw-semibold mb-1"><i class="bi bi-exclamation-triangle-fill"></i> Please correct the following errors:</div>
                <ul class="mb-0 ps-3">
                    <?php foreach ($errors as $err): ?>
                        <li><?= $err ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- ===== EQUIPMENT FORM ===== -->
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <form method="post" novalidate>
                    <?= csrf_field() ?>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="equipment_type">Equipment Type <span class="text-danger">*</span></label>
                            <input
                                class="form-control"
                                id="equipment_type"
                                name="equipment_type"
                                value="<?= e($equipment['equipment_type']) ?>"
                                placeholder="e.g., Laptop, Desktop, Printer, Router"
                                maxlength="50"
                                required
                                autofocus
                            >
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="serial_number">Serial Number <span class="text-danger">*</span></label>
                            <input
                                class="form-control"
                                id="serial_number"
                                name="serial_number"
                                value="<?= e($equipment['serial_number']) ?>"
                                placeholder="e.g., LNV-E14-0008"
                                maxlength="50"
                                required
                            >
                            <div class="form-text">Must be unique across all equipment.</div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="brand">Brand <span class="text-danger">*</span></label>
                            <input
                                class="form-control"
                                id="brand"
                                name="brand"
                                value="<?= e($equipment['brand']) ?>"
                                placeholder="e.g., Lenovo, Dell, HP"
                                maxlength="50"
                                required
                            >
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="model">Model <span class="text-danger">*</span></label>
                            <input
                                class="form-control"
                                id="model"
                                name="model"
                                value="<?= e($equipment['model']) ?>"
                                placeholder="e.g., ThinkPad E14"
                                maxlength="50"
                                required
                            >
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="status">Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="status" name="status" required>
                                <?php foreach (EQUIPMENT_STATUSES as $st): ?>
                                    <option value="<?= e($st) ?>" <?= $equipment['status'] === $st ? 'selected' : '' ?>>
                                        <?= e($st) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">To remove equipment from service, set status to <strong>Retired</strong> (records are never deleted).</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="warranty_expiry">Warranty Expiry <span class="text-muted fw-normal">(optional)</span></label>
                            <input
                                class="form-control"
                                id="warranty_expiry"
                                name="warranty_expiry"
                                type="date"
                                value="<?= e($equipment['warranty_expiry']) ?>"
                            >
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label" for="assigned_user_id">Assigned Holder <span class="text-muted fw-normal">(active users only)</span></label>
                        <select class="form-select" id="assigned_user_id" name="assigned_user_id">
                            <option value="">— Unassigned —</option>
                            <?php foreach ($activeUsers as $u): ?>
                                <option value="<?= (int)$u['user_id'] ?>" <?= (string)$equipment['assigned_user_id'] === (string)$u['user_id'] ? 'selected' : '' ?>>
                                    <?= e($u['full_name']) ?> (@<?= e($u['username']) ?> · <?= e($u['department_name']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text" id="holderHelp">
                            Assigning a holder sets status to <strong>Assigned</strong>. Retired equipment cannot have a holder.
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check2-circle"></i> <?= $isEdit ? 'Save Changes' : 'Add Equipment' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const statusSelect = document.getElementById('status');
    const holderSelect = document.getElementById('assigned_user_id');

    function syncFields(source) {
        if (source === 'holder') {
            if (holderSelect.value !== '' && statusSelect.value === 'In Stock') {
                statusSelect.value = 'Assigned';
            } else if (holderSelect.value === '' && statusSelect.value === 'Assigned') {
                statusSelect.value = 'In Stock';
            }
        }

        if (statusSelect.value === 'Retired') {
            holderSelect.value = '';
            holderSelect.disabled = true;
        } else {
            holderSelect.disabled = false;
        }
    }

    statusSelect.addEventListener('change', function () { syncFields('status'); });
    holderSelect.addEventListener('change', function () { syncFields('holder'); });
    syncFields('init');
});
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
