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
$sql = "SELECT t.ticket_id, t.title, c.category_name, p.priority_name,
               t.status, u.full_name AS created_by, t.due_at,
               a.full_name AS assigned_to, s.sla_state
        FROM tickets t
        INNER JOIN users u      ON u.user_id        = t.created_by
        INNER JOIN categories c ON c.category_id    = t.category_id
        INNER JOIN priorities p ON p.priority_id    = t.priority_id
        LEFT JOIN users a       ON a.user_id        = t.assigned_to
        LEFT JOIN v_ticket_sla s ON s.ticket_id     = t.ticket_id";

$where = [];
$params = [];
$me = current_user();

if (has_role('Employee')) {
    $where[] = 't.created_by = ?';
    $params[] = $me['user_id'];
}

if (has_role('Technician')){

    $view = $_GET['view'] ?? '';
    if ($view === 'mine') {
        $where[] = 't.assigned_to = ?';
        $params[] = $me['user_id'];
    }
    
}

if (has_role('Admin')) {
    
    $view = $_GET['unassigned'] ?? '';
    if ($view === '1') {
        $where[] = 't.assigned_to IS NULL';

    }
}

if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= ' ORDER BY t.created_at DESC';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$tickets = $stmt->fetchAll();

$pageTitle = 'Tickets';
require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Tickets</h1>
    <?php if (has_role('Employee')): ?>
        <a class="btn btn-primary" href="create.php"><i class="bi bi-plus-lg"></i> New Ticket</a>
    <?php endif; ?>
</div>

<?php if (has_role('Technician')): ?>
    <?php $isMine = ($_GET['view'] ?? '') === 'mine'; ?>
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link <?= $isMine ? 'active' : '' ?>" href="index.php?view=mine">Assigned to Me</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= !$isMine ? 'active' : '' ?>" href="index.php">All Tickets</a>
        </li>
    </ul>
<?php endif; ?>

<?php if (has_role('Admin')): ?>
    <?php $isUnassigned = ($_GET['unassigned'] ?? '') === '1'; ?>
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link <?= $isUnassigned ? 'active' : '' ?>" href="index.php?unassigned=1">Unassigned Tickets</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= !$isUnassigned ? 'active' : '' ?>" href="index.php">All tickets</a>
        </li>
    </ul>
<?php endif; ?>


<!-- ===== FILTER BAR (GET form) ===== -->
<!-- TODO(Tickets): status / category / priority / SLA selects + search box -->

<!-- ===== TICKET TABLE ===== -->
<!-- Columns: #, Title (link to view.php?id=), Category, Priority, Status badge,
     Created by, Assigned to, Due, SLA badge -->
<!-- TODO(Tickets): table.table.table-hover; show "No tickets found" when empty -->
<table class="table table-hover">
    <thead>
        <tr>
            <th>#</th>
            <th>Title</th>
            <th>Category</th>
            <th>Priority</th>
            <th>Created by</th>
            <th>Due</th>
            <th>Assigned to</th>
            <th>SLA</th>
            <th>Status</th>

        </tr>
    </thead>
    <tbody>
        <?php if (!$tickets): ?>
            <tr><td colspan="9" class="text-center text-muted">No tickets found. </td></tr>
        <?php endif; ?>   
        <?php foreach ($tickets as $t): ?>
            <tr>
                <td><?= e($t['ticket_id']) ?> </td>
                <td>
                    <a href="view.php?id=<?= e($t['ticket_id']) ?>">
                        <?= e($t['title']) ?>
                    </a>
                </td>
                <td><?= e($t['category_name']) ?> </td>
                <td><?= e($t['priority_name']) ?> </td>
                <td><?= e($t['created_by']) ?> </td>
                <td><?= e($t['due_at']) ?> </td>
                <td><?= e($t['assigned_to'] ?? '—') ?> </td>
                <td>
                    <span class="badge <?= sla_badge($t['sla_state']) ?>">
                        <?= e($t['sla_state']) ?>  
                    </span>
                </td>
                <td>
                    <span class="badge <?= status_badge($t['status']) ?>">
                        <?= e($t['status']) ?>  
                    </span>
                </td>
            </tr>     
        <?php endforeach; ?>
    </tbody>
</table>




<?php require __DIR__ . '/../includes/footer.php'; ?>

