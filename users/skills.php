<?php
/**
 * users/skills.php?id=N — manage a Technician's qualified categories (Admin)
 * OWNER: Harold
 *
 * Shows: technician name + checkbox list of all categories,
 *        checked = row exists in technician_skills (show added_at)
 *
 * On POST (in a transaction):
 *   - INSERT rows for newly checked categories
 *   - DELETE rows for unchecked categories
 *   - Composite PK (user_id, category_id) blocks duplicates at DB level
 *   - Reject if user is not role = 'Technician'
 *   - Optional: warn if removing a skill the tech has unresolved tickets in
 */
require_once __DIR__ . '/../includes/auth.php';
require_role(['Admin']);

$id = (int)($_GET['id'] ?? 0);

// ---------------------------------------------------------------------
// 1. LOAD TECHNICIAN & VERIFY ROLE (BR5)
// ---------------------------------------------------------------------
$stmt = db()->prepare(
    'SELECT u.user_id, u.full_name, u.username, u.role, u.is_active, d.department_name
     FROM users u
     JOIN departments d ON u.department_id = d.department_id
     WHERE u.user_id = ?'
);
$stmt->execute([$id]);
$tech = $stmt->fetch();

if (!$tech) {
    flash('danger', 'User not found.');
    redirect(BASE_URL . '/users/index.php');
}

if ($tech['role'] !== 'Technician') {
    flash('danger', 'Skills can only be managed for users with the Technician role.');
    redirect(BASE_URL . '/users/index.php');
}

// ---------------------------------------------------------------------
// 2. LOAD CATEGORIES, CURRENT SKILLS & UNRESOLVED TICKET COUNTS
// ---------------------------------------------------------------------
$categories = db()->query(
    'SELECT category_id, category_name FROM categories ORDER BY category_name ASC'
)->fetchAll();

$categoryNames = [];
foreach ($categories as $cat) {
    $categoryNames[(int)$cat['category_id']] = $cat['category_name'];
}

$skillStmt = db()->prepare(
    'SELECT category_id, added_at FROM technician_skills WHERE user_id = ?'
);
$skillStmt->execute([$id]);
$currentSkills = [];
foreach ($skillStmt->fetchAll() as $row) {
    $currentSkills[(int)$row['category_id']] = $row['added_at'];
}

$ticketStmt = db()->prepare(
    "SELECT category_id, COUNT(*) AS cnt
     FROM tickets
     WHERE assigned_to = ? AND status IN ('Open', 'In Progress')
     GROUP BY category_id"
);
$ticketStmt->execute([$id]);
$unresolvedByCategory = [];
foreach ($ticketStmt->fetchAll() as $row) {
    $unresolvedByCategory[(int)$row['category_id']] = (int)$row['cnt'];
}

// ---------------------------------------------------------------------
// 3. HANDLE POST (SAVE SKILLS IN A TRANSACTION)
// ---------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $rawSelected = $_POST['categories'] ?? [];
    if (!is_array($rawSelected)) {
        $rawSelected = [];
    }

    // Keep only valid category IDs from DB and deduplicate
    $validCategoryIds = array_keys($categoryNames);
    $selectedIds = array_values(array_unique(array_filter(
        array_map('intval', $rawSelected),
        static fn(int $catId): bool => in_array($catId, $validCategoryIds, true)
    )));

    $currentIds = array_keys($currentSkills);
    $toAdd      = array_values(array_diff($selectedIds, $currentIds));
    $toRemove   = array_values(array_diff($currentIds, $selectedIds));

    $pdo = db();
    try {
        $pdo->beginTransaction();

        if (!empty($toAdd)) {
            $insertStmt = $pdo->prepare(
                'INSERT INTO technician_skills (user_id, category_id) VALUES (?, ?)'
            );
            foreach ($toAdd as $catId) {
                $insertStmt->execute([$id, $catId]);
            }
        }

        if (!empty($toRemove)) {
            $deleteStmt = $pdo->prepare(
                'DELETE FROM technician_skills WHERE user_id = ? AND category_id = ?'
            );
            foreach ($toRemove as $catId) {
                $deleteStmt->execute([$id, $catId]);
            }
        }

        $pdo->commit();

        // Optional warning if any removed skill still has unresolved tickets assigned
        $warnedCategories = [];
        foreach ($toRemove as $catId) {
            if (!empty($unresolvedByCategory[$catId])) {
                $warnedCategories[] = $categoryNames[$catId] . ' (' . $unresolvedByCategory[$catId] . ' unresolved)';
            }
        }
        if (!empty($warnedCategories)) {
            flash(
                'warning',
                'Note: ' . e($tech['full_name']) . ' still has unresolved tickets in removed skill(s): '
                . e(implode(', ', $warnedCategories)) . '.'
            );
        }

        flash('success', 'Skills updated for "' . e($tech['full_name']) . '".');
        redirect(BASE_URL . '/users/skills.php?id=' . $id);
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        flash('danger', 'Failed to update skills: ' . e($e->getMessage()));
        redirect(BASE_URL . '/users/skills.php?id=' . $id);
    }
}

$pageTitle = 'Technician Skills';
require __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-7 col-md-9">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h3 mb-0">Technician Skills</h1>
            <a href="index.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Back to Users
            </a>
        </div>

        <!-- Technician Summary Card -->
        <div class="card shadow-sm mb-3">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="h5 mb-1"><?= e($tech['full_name']) ?></h2>
                    <div class="text-muted small">
                        <code>@<?= e($tech['username']) ?></code> · <?= e($tech['department_name']) ?>
                    </div>
                </div>
                <div class="d-flex gap-1">
                    <span class="badge text-bg-info">Technician</span>
                    <?php if ($tech['is_active']): ?>
                        <span class="badge text-bg-success">Active</span>
                    <?php else: ?>
                        <span class="badge text-bg-danger">Inactive</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ===== SKILLS FORM (checkbox per category) ===== -->
        <form method="post" class="card shadow-sm">
            <?= csrf_field() ?>
            <div class="card-header bg-light fw-semibold">
                Qualified Ticket Categories
            </div>

            <div class="list-group list-group-flush">
                <?php if (empty($categories)): ?>
                    <div class="list-group-item text-muted text-center py-4">
                        No categories available.
                    </div>
                <?php else: ?>
                    <?php foreach ($categories as $cat): ?>
                        <?php
                        $catId      = (int)$cat['category_id'];
                        $isChecked  = isset($currentSkills[$catId]);
                        $addedAt    = $currentSkills[$catId] ?? null;
                        $openCount  = $unresolvedByCategory[$catId] ?? 0;
                        ?>
                        <label class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3" for="cat_<?= $catId ?>">
                            <div class="form-check mb-0">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    id="cat_<?= $catId ?>"
                                    name="categories[]"
                                    value="<?= $catId ?>"
                                    <?= $isChecked ? 'checked' : '' ?>
                                >
                                <span class="form-check-label fw-medium"><?= e($cat['category_name']) ?></span>
                            </div>

                            <div class="d-flex align-items-center gap-2">
                                <?php if ($openCount > 0): ?>
                                    <span class="badge text-bg-warning" title="Technician currently has unresolved tickets in this category">
                                        <?= $openCount ?> unresolved
                                    </span>
                                <?php endif; ?>

                                <?php if ($isChecked && $addedAt): ?>
                                    <span class="text-muted small">
                                        Added <?= e(date('M j, Y', strtotime($addedAt))) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted small">Not assigned</span>
                                <?php endif; ?>
                            </div>
                        </label>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="card-footer bg-white d-flex justify-content-end gap-2">
                <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check2-circle"></i> Save Skills
                </button>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
