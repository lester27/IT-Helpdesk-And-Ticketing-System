<?php
/**
 * tickets/index.php — ticket list
 * OWNER: Lester
 *
 * Visibility by role:
 *   Employee   : only tickets WHERE created_by = me
 *   Technician : tickets assigned to me (tab) + all tickets (tab)
 *   Admin      : all tickets, with an "Unassigned" filter (assigned_to IS NULL)
 *
 * Base query = reports query R1 (INNER JOIN tickets/users/categories/priorities)
 * plus LEFT JOIN v_ticket_sla for the SLA badge.
 *
 * Filters (GET params): status, category_id, priority_id, sla_state, q (title search)
 */
require_once __DIR__ . '/../includes/auth.php';
require_login();

// TODO(Tickets): build query + filters by role, using prepared statements
$tickets = [];

$pageTitle = 'Tickets';
require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Tickets</h1>
    <?php if (has_role('Employee')): ?>
        <a class="btn btn-primary" href="create.php"><i class="bi bi-plus-lg"></i> New Ticket</a>
    <?php endif; ?>
</div>

<!-- ===== FILTER BAR (GET form) ===== -->
<!-- TODO(Tickets): status / category / priority / SLA selects + search box -->

<!-- ===== TICKET TABLE ===== -->
<!-- Columns: #, Title (link to view.php?id=), Category, Priority, Status badge,
     Created by, Assigned to, Due, SLA badge -->
<!-- TODO(Tickets): table.table.table-hover; show "No tickets found" when empty -->

<?php require __DIR__ . '/../includes/footer.php'; ?>

