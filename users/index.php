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

$currentUser = current_user();

// ---------------------------------------------------------------------
// 1. POST ACTION: Toggle is_active (Activate / Deactivate) — BR4
// ---------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_active') {
    verify_csrf();

    $targetUserId = (int)($_POST['user_id'] ?? 0);

    // Rule: You cannot deactivate your own account
    if ($targetUserId === (int)$currentUser['user_id']) {
        flash('danger', 'You cannot deactivate your own account.');
        redirect(BASE_URL . '/users/index.php');
    }

    // Fetch current user status
    $stmt = db()->prepare('SELECT user_id, full_name, is_active FROM users WHERE user_id = ?');
    $stmt->execute([$targetUserId]);
    $targetUser = $stmt->fetch();

    if (!$targetUser) {
        flash('danger', 'User not found.');
        redirect(BASE_URL . '/users/index.php');
    }

    $newStatus = $targetUser['is_active'] ? 0 : 1;
    $updateStmt = db()->prepare('UPDATE users SET is_active = ? WHERE user_id = ?');
    $updateStmt->execute([$newStatus, $targetUserId]);

    $statusLabel = $newStatus ? 'activated' : 'deactivated';
    flash('success', 'User "' . e($targetUser['full_name']) . '" has been ' . $statusLabel . '.');
    redirect(BASE_URL . '/users/index.php');
}

// ---------------------------------------------------------------------
// 2. LOAD DEPARTMENTS (for filter dropdown)
// ---------------------------------------------------------------------
$departments = db()->query('SELECT department_id, department_name FROM departments ORDER BY department_name ASC')->fetchAll();

// ---------------------------------------------------------------------
// 3. QUERY USERS WITH FILTERS
// ---------------------------------------------------------------------
$filterRole   = trim($_GET['role'] ?? '');
$filterDept   = isset($_GET['department_id']) && $_GET['department_id'] !== '' ? (int)$_GET['department_id'] : null;
$filterActive = $_GET['active'] ?? '';

$sql = 'SELECT u.user_id, u.full_name, u.username, u.email, u.role, u.is_active, u.created_at, d.department_name
        FROM users u
        JOIN departments d ON u.department_id = d.department_id
        WHERE 1=1';
$params = [];

if ($filterRole !== '' && in_array($filterRole, ROLES, true)) {
    $sql .= ' AND u.role = ?';
    $params[] = $filterRole;
}

if ($filterDept !== null) {
    $sql .= ' AND u.department_id = ?';
    $params[] = $filterDept;
}

if ($filterActive !== '') {
    $sql .= ' AND u.is_active = ?';
    $params[] = ($filterActive === '1') ? 1 : 0;
}

$sql .= ' ORDER BY u.full_name ASC';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$pageTitle = 'Users';
require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Users</h1>
    <a class="btn btn-primary" href="form.php"><i class="bi bi-person-plus"></i> Add User</a>
</div>

<!-- ===== FILTER BAR ===== -->
<div class="card mb-4 shadow-sm">
    <div class="card-body">
        <form method="get" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label small text-muted" for="role">Role</label>
                <select class="form-select form-select-sm" id="role" name="role">
                    <option value="">All Roles</option>
                    <?php foreach (ROLES as $role): ?>
                        <option value="<?= e($role) ?>" <?= $filterRole === $role ? 'selected' : '' ?>>
                            <?= e($role) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label small text-muted" for="department_id">Department</label>
                <select class="form-select form-select-sm" id="department_id" name="department_id">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?= (int)$dept['department_id'] ?>" <?= $filterDept === (int)$dept['department_id'] ? 'selected' : '' ?>>
                            <?= e($dept['department_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label small text-muted" for="active">Status</label>
                <select class="form-select form-select-sm" id="active" name="active">
                    <option value="">All Statuses</option>
                    <option value="1" <?= $filterActive === '1' ? 'selected' : '' ?>>Active</option>
                    <option value="0" <?= $filterActive === '0' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>

            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-secondary w-100">Filter</button>
                <a href="index.php" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- ===== USERS TABLE ===== -->
<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Full Name</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Department</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">No users found matching the criteria.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                        <?php
                        $isSelf = (int)$user['user_id'] === (int)$currentUser['user_id'];
                        $roleBadge = match ($user['role']) {
                            'Admin'      => 'text-bg-dark',
                            'Technician' => 'text-bg-info',
                            default      => 'text-bg-secondary',
                        };
                        ?>
                        <tr>
                            <td class="fw-semibold"><?= e($user['full_name']) ?></td>
                            <td><code><?= e($user['username']) ?></code></td>
                            <td><?= e($user['email']) ?></td>
                            <td><?= e($user['department_name']) ?></td>
                            <td><span class="badge <?= $roleBadge ?>"><?= e($user['role']) ?></span></td>
                            <td>
                                <?php if ($user['is_active']): ?>
                                    <span class="badge text-bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge text-bg-danger">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="small text-muted"><?= date('M j, Y', strtotime($user['created_at'])) ?></td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a class="btn btn-outline-primary" href="form.php?id=<?= (int)$user['user_id'] ?>">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>

                                    <?php if ($user['role'] === 'Technician'): ?>
                                        <a class="btn btn-outline-info" href="skills.php?id=<?= (int)$user['user_id'] ?>">
                                            <i class="bi bi-tools"></i> Skills
                                        </a>
                                    <?php endif; ?>

                                    <?php if (!$isSelf): ?>
                                        <form method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to <?= $user['is_active'] ? 'deactivate' : 'activate' ?> this user?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="toggle_active">
                                            <input type="hidden" name="user_id" value="<?= (int)$user['user_id'] ?>">
                                            <?php if ($user['is_active']): ?>
                                                <button type="submit" class="btn btn-outline-danger" title="Deactivate User">
                                                    <i class="bi bi-person-x"></i> Deactivate
                                                </button>
                                            <?php else: ?>
                                                <button type="submit" class="btn btn-outline-success" title="Activate User">
                                                    <i class="bi bi-person-check"></i> Activate
                                                </button>
                                            <?php endif; ?>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
