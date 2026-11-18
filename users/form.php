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
$errors = [];

// Load departments for the dropdown
$departments = db()->query('SELECT department_id, department_name FROM departments ORDER BY department_name ASC')->fetchAll();

// Default values
$user = [
    'full_name'     => '',
    'username'      => '',
    'email'         => '',
    'department_id' => '',
    'role'          => 'Employee',
];

if ($isEdit) {
    $stmt = db()->prepare('SELECT user_id, full_name, username, email, department_id, role, is_active FROM users WHERE user_id = ?');
    $stmt->execute([$id]);
    $existing = $stmt->fetch();

    if (!$existing) {
        flash('danger', 'User not found.');
        redirect(BASE_URL . '/users/index.php');
    }
    $user = $existing;
}

// ---------------------------------------------------------------------
// HANDLE FORM SUBMISSION
// ---------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $fullName     = trim($_POST['full_name'] ?? '');
    $username     = trim($_POST['username'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $departmentId = (int)($_POST['department_id'] ?? 0);
    $role         = trim($_POST['role'] ?? '');
    $password     = $_POST['password'] ?? '';

    // Repopulate user array so inputs keep values on validation error
    $user['full_name']     = $fullName;
    $user['username']      = $username;
    $user['email']         = $email;
    $user['department_id'] = $departmentId;
    $user['role']          = $role;

    // Validation
    if ($fullName === '') {
        $errors[] = 'Full name is required.';
    }

    if ($username === '') {
        $errors[] = 'Username is required.';
    } elseif (!preg_match('/^[a-zA-Z0-9._-]+$/', $username)) {
        $errors[] = 'Username may only contain letters, numbers, dots, dashes, and underscores.';
    }

    if ($email === '') {
        $errors[] = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    $validDeptIds = array_column($departments, 'department_id');
    if (!in_array($departmentId, array_map('intval', $validDeptIds), true)) {
        $errors[] = 'Please select a valid department.';
    }

    if (!in_array($role, ROLES, true)) {
        $errors[] = 'Please select a valid role.';
    }

    // Password validation
    if (!$isEdit && $password === '') {
        $errors[] = 'Password is required when creating a new user.';
    } elseif ($password !== '' && strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long.';
    }

    // Rule: Changing a Technician to another role while having unresolved tickets
    if ($isEdit && $existing['role'] === 'Technician' && $role !== 'Technician') {
        $ticketCheckStmt = db()->prepare("SELECT COUNT(*) FROM tickets WHERE assigned_to = ? AND status IN ('Open', 'In Progress')");
        $ticketCheckStmt->execute([$id]);
        $unresolvedCount = (int)$ticketCheckStmt->fetchColumn();

        if ($unresolvedCount > 0) {
            $errors[] = "Cannot change role from Technician to {$role}: this technician still has {$unresolvedCount} unresolved ticket(s) assigned.";
        }
    }

    // Save if no validation errors
    if (empty($errors)) {
        try {
            if ($isEdit) {
                if ($password !== '') {
                    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = db()->prepare('UPDATE users SET full_name = ?, username = ?, email = ?, department_id = ?, role = ?, password_hash = ? WHERE user_id = ?');
                    $stmt->execute([$fullName, $username, $email, $departmentId, $role, $passwordHash, $id]);
                } else {
                    $stmt = db()->prepare('UPDATE users SET full_name = ?, username = ?, email = ?, department_id = ?, role = ? WHERE user_id = ?');
                    $stmt->execute([$fullName, $username, $email, $departmentId, $role, $id]);
                }

                flash('success', 'User "' . e($fullName) . '" was updated successfully.');
                redirect(BASE_URL . '/users/index.php');
            } else {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = db()->prepare('INSERT INTO users (full_name, username, email, department_id, role, password_hash, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)');
                $stmt->execute([$fullName, $username, $email, $departmentId, $role, $passwordHash]);

                flash('success', 'User "' . e($fullName) . '" was created successfully.');
                redirect(BASE_URL . '/users/index.php');
            }
        } catch (PDOException $e) {
            // Catch duplicate entry (BR3)
            if ($e->getCode() === '23000' || (isset($e->errorInfo[1]) && $e->errorInfo[1] === 1062)) {
                $msg = $e->getMessage();
                if (stripos($msg, 'uq_users_username') !== false || stripos($msg, 'username') !== false) {
                    $errors[] = 'The username "' . e($username) . '" is already taken. Please choose another.';
                } elseif (stripos($msg, 'uq_users_email') !== false || stripos($msg, 'email') !== false) {
                    $errors[] = 'The email "' . e($email) . '" is already registered. Please choose another.';
                } else {
                    $errors[] = 'A user with this username or email already exists.';
                }
            } else {
                $errors[] = 'An unexpected database error occurred: ' . e($e->getMessage());
            }
        }
    }
}

$pageTitle = $isEdit ? 'Edit User' : 'Add User';
require __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-7 col-md-9">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h3 mb-0"><?= e($pageTitle) ?></h1>
            <a href="index.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Back to Users
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

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <form method="post" novalidate>
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label" for="full_name">Full Name <span class="text-danger">*</span></label>
                        <input class="form-control" id="full_name" name="full_name" value="<?= e($user['full_name']) ?>" required autofocus>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="username">Username <span class="text-danger">*</span></label>
                            <input class="form-control" id="username" name="username" value="<?= e($user['username']) ?>" required>
                            <div class="form-text">Must be unique. Letters, numbers, dots, and hyphens.</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="email">Email Address <span class="text-danger">*</span></label>
                            <input class="form-control" id="email" name="email" type="email" value="<?= e($user['email']) ?>" required>
                            <div class="form-text">Must be unique. Used for system notifications.</div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="department_id">Department <span class="text-danger">*</span></label>
                            <select class="form-select" id="department_id" name="department_id" required>
                                <option value="">Select department...</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?= (int)$dept['department_id'] ?>" <?= (int)$user['department_id'] === (int)$dept['department_id'] ? 'selected' : '' ?>>
                                        <?= e($dept['department_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="role">Role <span class="text-danger">*</span></label>
                            <select class="form-select" id="role" name="role" required>
                                <?php foreach (ROLES as $roleOption): ?>
                                    <option value="<?= e($roleOption) ?>" <?= $user['role'] === $roleOption ? 'selected' : '' ?>>
                                        <?= e($roleOption) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label" for="password">
                            Password <?= $isEdit ? '<span class="text-muted fw-normal">(leave blank to keep current password)</span>' : '<span class="text-danger">*</span>' ?>
                        </label>
                        <input class="form-control" id="password" name="password" type="password" <?= $isEdit ? '' : 'required' ?> autocomplete="new-password">
                        <div class="form-text">At least 6 characters.</div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check2-circle"></i> <?= $isEdit ? 'Save Changes' : 'Create User' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
