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

// TODO(Skills): load technician + categories + current skills; handle POST

$pageTitle = 'Technician Skills';
require __DIR__ . '/../includes/header.php';
?>

<h1 class="h3 mb-3">Technician Skills</h1>

<!-- ===== SKILLS FORM (checkbox per category) ===== -->
<form method="post" class="card card-body">
    <?= csrf_field() ?>
    <!-- TODO(Skills): checkboxes -->
</form>

<?php require __DIR__ . '/../includes/footer.php'; ?>

