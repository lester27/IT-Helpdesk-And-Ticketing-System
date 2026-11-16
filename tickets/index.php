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

$categories = db()->query('SELECT category_id, category_name FROM categories 
    ORDER BY category_name')->fetchALL();
$priorities =db()->query('SELECT priority_id, priority_name FROM priorities 
    ORDER BY priority_id')->fetchALL();

$status     = $_GET['status'] ?? '';
$categoryId = (int)($_GET['category_id'] ?? 0);
$priorityId = (int)($_GET['priority_id'] ?? 0);
$slaState   = $_GET['sla_state'] ?? '';
$q          = trim($_GET['q']  ?? '');

if (in_array($status, TICKET_STATUSES, true)) {
    $where [] = 't.status = ?';
    $params [] = $status;
}

if ($categoryId > 0){
    $where [] = 't.category_id = ?';
    $params [] = $categoryId;
}

if ($priorityId > 0){
    $where [] = 't.priority_id = ?';
    $params [] = $priorityId;
}

if (in_array($slaState, ['On Track', 'At Risk', 'Breached', 'Met'], true)) {
    $where[] = 's.sla_state = ? COLLATE utf8mb4_unicode_ci';
    $params [] = $slaState;
}

if ($q !== '') {
    $where [] = 't.title LIKE ?';
    $params [] = '%'. $q . '%';
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
<form method="get" class="row g-2 mb-3">
    <?php if (has_role('Technician') && ($_GET['view'] ?? '') === 'mine'): ?>
        <input type="hidden" name="view" value="mine">
    <?php endif; ?>
    <?php if (has_role('Admin') && ($_GET['unassigned'] ?? '') === '1'): ?>
        <input type="hidden" name="unassigned" value="1">
    <?php endif; ?>

    <!-- Status Select-->
    <div class="col-md-2">
        <select name="status" class="form-select">
            <option value="">All Statuses</option>
            <?php foreach (TICKET_STATUSES as $st): ?>
                <option value="<?= e($st) ?>" <?= $status === $st ? 'selected' : '' ?>>
                    <?= e($st) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Category Select -->
    <div class="col-md-2">
        <select name="category_id" class="form-select">
            <option value="">All Categories</option>
            <?php foreach ($categories as $c): ?>
                <option value="<?= e($c['category_id']) ?>" <?= $categoryId === (int)$c['category_id'] ? 'selected' : '' ?>>
                    <?= e($c['category_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Priority select) -->
    <div class="col-md-2">
        <select name="priority_id" class="form-select">
            <option value="">All priorities</option>
            <?php foreach ($priorities as $p): ?>
                <option value="<?= e($p['priority_id']) ?>" <?= $priorityId === (int)$p['priority_id'] ? 'selected' : '' ?>>
                    <?= e($p['priority_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <!-- SLA select -->
    
    <div class="col-md-2">
        <select name="sla_state" class="form-select">
            <option value="">All SLA</option>
            <?php foreach (['On Track', 'At Risk', 'Breached', 'Met'] as $s): ?>
                <option value="<?= e($s) ?>" <?= $slaState === $s ? 'selected' : '' ?>>
                    <?= e($s) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Title search -->
    <div class="col-md-3">
        <input type="text" name="q" class="form-control" placeholder="Search title..." value="<?= e($q) ?>">
    </div>

    <div class="col-md-auto">
        <button type="submit" class="btn btn-outline-primary">Filter</button>
        <a href="index.php" class="btn btn-outline-secondary">Reset</a>
    </div>
</form>

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

