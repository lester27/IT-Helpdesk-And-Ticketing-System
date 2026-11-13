<?php
/**
 * tickets/create.php — report a new problem
 * OWNER: Lauren
 *
 * Who: Employee (Admin may also create on behalf of someone — optional)
 *
 * Form fields:
 *   title (required, max 255), description (required),
 *   category_id (select from categories), priority_id (select from priorities),
 *   equipment_id (optional select: equipment where assigned_user_id = me
 *                 AND status <> 'Retired')   <- BR19
 *
 * On POST:
 *   - verify_csrf(), validate required fields (BR6)
 *   - INSERT INTO tickets (title, description, category_id, priority_id, created_by, equipment_id)
 *     DO NOT send status, created_at, or due_at:
 *       status defaults to 'Open' (BR7), due_at is set by trigger trg_tickets_bi (BR11)
 *   - flash success, redirect to view.php?id=<lastInsertId>
 */
require_once __DIR__ . '/../includes/auth.php';
require_login();

// TODO(Tickets): load categories, priorities, my non-retired equipment for selects
// TODO(Tickets): handle POST

$pageTitle = 'New Ticket';
require __DIR__ . '/../includes/header.php';
?>

<h1 class="h3 mb-3">Report a Problem</h1>

<!-- ===== NEW TICKET FORM ===== -->
<form method="post" class="card card-body">
    <?= csrf_field() ?>
    <!-- TODO(Tickets): title, description, category, priority, equipment inputs -->
    <!-- TODO(Tickets): submit + cancel buttons -->
</form>

<?php require __DIR__ . '/../includes/footer.php'; ?>

