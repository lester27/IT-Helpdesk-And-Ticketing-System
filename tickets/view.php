<?php
/**
 * tickets/view.php?id=N — ticket detail + history
 * OWNER: Lester
 *
 * Access: Employee only if created_by = me; Technician and Admin can view all.
 *
 * Sections:
 *   1. Ticket info: title, description, category, priority, status badge,
 *      creator, assignee, equipment, created_at, due_at, resolved_at,
 *      SLA badge + overdue_hours (from v_ticket_sla)
 *   2. Timeline: ticket_updates ORDER BY created_at, joined to users for names.
 *      Rows with old_status/new_status show "Open -> In Progress"; NULL = plain comment.
 *   3. Action forms (hidden when status = 'Resolved' — BR18):
 *        - Add comment          -> POST update.php (action=comment)       anyone who can view
 *        - Change status        -> POST update.php (action=status)        assigned Technician / Admin
 *        - Assign technician    -> POST assign.php                        Admin only (BR17)
 */
require_once __DIR__ . '/../includes/auth.php';
require_login();

$id = (int)($_GET['id'] ?? 0);

// TODO(Tickets): load ticket (+ joins + SLA), 404 if missing, enforce access rule
// TODO(Tickets): load timeline from ticket_updates
// TODO(Tickets): if Admin, load qualified technicians for assign dropdown (see assign.php)

$pageTitle = 'Ticket #' . $id;
require __DIR__ . '/../includes/header.php';
?>

<!-- ===== TICKET HEADER (title, badges) ===== -->

<div class="row g-4">
    <div class="col-lg-8">
        <!-- ===== DETAILS CARD ===== -->
        <!-- ===== TIMELINE (list-group) ===== -->
        <!-- ===== ADD COMMENT FORM ===== -->
    </div>
    <div class="col-lg-4">
        <!-- ===== STATUS CHANGE FORM (next status only — BR16) ===== -->
        <!-- ===== ASSIGN FORM (Admin only — BR17) ===== -->
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>

