<?php
/**
 * departments/index.php — list + add + rename departments (Admin)
 * OWNER: Miguel
 *
 * Single page: table of departments with user count, inline "Add" form,
 * rename via small form per row.
 * Rules: department_name UNIQUE; no delete (users reference it — ON DELETE RESTRICT).
 */
require_once __DIR__ . '/../includes/auth.php';
require_role(['Admin']);

$pdo  = db();
$self = BASE_URL . '/departments/index.php';

function save_department(PDO $pdo, ?int $id, string $name): ?string
{
    $name = trim($name);
    if ($name === '')            return 'Department name is required.';
    if (mb_strlen($name) > 100)  return 'Department name must be 100 characters or fewer.';

    // Friendly duplicate check (the UNIQUE index remains the real guard)
    $sql = 'SELECT COUNT(*) FROM departments WHERE department_name = ?' . ($id ? ' AND department_id <> ?' : '');
    $st = $pdo->prepare($sql);
    $st->execute($id ? [$name, $id] : [$name]);
    if ((int)$st->fetchColumn() > 0) return 'A department with that name already exists.';

    try {
        if ($id) {
            $pdo->prepare('UPDATE departments SET department_name = ? WHERE department_id = ?')->execute([$name, $id]);
        } else {
            $pdo->prepare('INSERT INTO departments (department_name) VALUES (?)')->execute([$name]);
        }
    } catch (PDOException $ex) {
        if ($ex->getCode() === '23000') return 'A department with that name already exists.';
        throw $ex;
    }
    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $err = save_department($pdo, null, $_POST['department_name'] ?? '');
        flash($err ? 'danger' : 'success', $err ?? 'Department added.');
    } elseif ($action === 'rename') {
        $id = (int)($_POST['department_id'] ?? 0);
        $chk = $pdo->prepare('SELECT COUNT(*) FROM departments WHERE department_id = ?');
        $chk->execute([$id]);
        if ($id <= 0 || !$chk->fetchColumn()) {
            flash('danger', 'Department not found.');
        } else {
            $err = save_department($pdo, $id, $_POST['department_name'] ?? '');
            flash($err ? 'danger' : 'success', $err ?? 'Department renamed.');
        }
    }
    // No delete action exists on purpose.
    redirect($self);
}

$departments = $pdo->query(
    'SELECT d.department_id, d.department_name, COUNT(u.user_id) AS user_count
       FROM departments d
       LEFT JOIN users u ON u.department_id = d.department_id
      GROUP BY d.department_id, d.department_name
      ORDER BY d.department_name'
)->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Departments';
require __DIR__ . '/../includes/header.php';
?>

<h1 class="h3 mb-3">Departments</h1>

<!-- ===== ADD DEPARTMENT FORM ===== -->
<form method="post" class="card card-body mb-4">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="add">
    <label for="new_name" class="form-label">Add department</label>
    <div class="input-group">
        <input type="text" id="new_name" name="department_name" class="form-control"
               maxlength="100" required placeholder="e.g. Finance">
        <button class="btn btn-primary" type="submit"><i class="bi bi-plus-lg"></i> Add department</button>
    </div>
</form>

<!-- ===== DEPARTMENTS TABLE ===== -->
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Department</th>
                    <th class="text-center">Users</th>
                    <th style="width:40%">Rename</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$departments): ?>
                <tr><td colspan="3" class="text-center text-muted py-4">No departments yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($departments as $d): ?>
                <tr>
                    <td><?= e($d['department_name']) ?></td>
                    <td class="text-center"><span class="badge text-bg-secondary"><?= (int)$d['user_count'] ?></span></td>
                    <td>
                        <form method="post" class="input-group input-group-sm">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="rename">
                            <input type="hidden" name="department_id" value="<?= (int)$d['department_id'] ?>">
                            <input type="text" name="department_name" class="form-control"
                                   value="<?= e($d['department_name']) ?>" maxlength="100" required
                                   aria-label="New name for <?= e($d['department_name']) ?>">
                            <button class="btn btn-outline-primary" type="submit">Rename</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
