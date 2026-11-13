<?php
/**
 * equipment/view.php?id=N — equipment detail + its ticket history
 * OWNER: Miguel
 *
 * Shows: all equipment fields, current holder, warranty state,
 *        list of tickets WHERE equipment_id = N (newest first),
 *        "Recurring issue" badge if >= 3 tickets in last 90 days (BR15)
 */
require_once __DIR__ . '/../includes/auth.php';
require_role(['Admin', 'Technician']);

$id = (int)($_GET['id'] ?? 0);

// TODO(Equipment): load equipment + holder; load related tickets

$pageTitle = 'Equipment #' . $id;
require __DIR__ . '/../includes/header.php';
?>

<!-- ===== DETAILS CARD ===== -->
<!-- ===== RELATED TICKETS TABLE ===== -->

<?php require __DIR__ . '/../includes/footer.php'; ?>

