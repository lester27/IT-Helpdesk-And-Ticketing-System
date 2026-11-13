<?php
/**
 * equipment/index.php — equipment list
 * OWNER: Miguel
 *
 * Access: Admin (full), Technician (read-only)
 *
 * Columns: type, brand, model, serial_number, status badge, warranty_expiry
 *          (highlight if expired / expiring in 30 days), current holder (LEFT JOIN users),
 *          ticket count last 90 days
 * Filters: status, type, "under warranty only"
 * Admin: "Add equipment" button -> form.php ; row "Edit" -> form.php?id=N
 */
require_once __DIR__ . '/../includes/auth.php';
require_role(['Admin', 'Technician']);

// TODO(Equipment): query equipment LEFT JOIN users (holder) + filters
$equipment = [];

$pageTitle = 'Equipment';
require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Equipment</h1>
    <?php if (has_role('Admin')): ?>
        <a class="btn btn-primary" href="form.php"><i class="bi bi-plus-lg"></i> Add Equipment</a>
    <?php endif; ?>
</div>

<!-- ===== FILTER BAR ===== -->
<!-- ===== EQUIPMENT TABLE (row link -> view.php?id=N) ===== -->

<?php require __DIR__ . '/../includes/footer.php'; ?>

