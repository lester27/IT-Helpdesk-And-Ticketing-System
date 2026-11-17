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

$sql = "SELECT tu.update_id, tu.user_id, tu.old_status,
               tu.new_status, tu.comment, tu.created_at,
               u.full_name AS author_name
        FROM ticket_updates tu
        INNER JOIN users u ON u.user_id = tu.user_id
        WHERE tu.ticket_id = ?
        ORDER BY tu.created_at";

$stmt = db()->prepare($sql);
$stmt->execute([$id]);
$updates = $stmt->fetchAll();

$sql = "SELECT t.ticket_id, t.title, c.category_name, p.priority_name,
               t.status, t.created_by AS creator_id, u.full_name AS creator_name,
               t.due_at, t.description, t.created_at, t.resolved_at, 
               a.full_name AS assigned_to, s.sla_state
        FROM tickets t
        INNER JOIN users u      ON u.user_id        = t.created_by
        INNER JOIN categories c ON c.category_id    = t.category_id
        INNER JOIN priorities p ON p.priority_id    = t.priority_id
        LEFT JOIN users a       ON a.user_id        = t.assigned_to
        LEFT JOIN v_ticket_sla s ON s.ticket_id     = t.ticket_id
        WHERE t.ticket_id = ?";

$stmt = db()->prepare($sql);
$stmt->execute([$id]);
$ticket = $stmt->fetch();

if (!$ticket) {
    http_response_code(404);
    echo '<h1>Ticket Not Found</h1>';
    exit;
}

if (has_role('Employee') && current_user()['user_id'] !== $ticket['creator_id']) {
    http_response_code(403);
    require __DIR__ . '/../errors/403.php';
    exit;
}

// TODO(Tickets): if Admin, load qualified technicians for assign dropdown (see assign.php)



$pageTitle = 'Ticket #' . $id;
require __DIR__ . '/../includes/header.php';
?>

<!-- ===== TICKET HEADER (title, badges) ===== -->

<div class="row g-4">
    <div class="col-lg-8">
        <h2 class="mb-3">Ticket Information</h2>
        <!-- ===== DETAILS CARD ===== -->
        <dl class="row">
            <dt class="col-sm-3">Ticket ID:</dt>
            <dd class="col-sm-9"><?= e($ticket['ticket_id']) ?></dd>
            <dt class="col-sm-3">Title:</dt>
            <dd class="col-sm-9"><?= e($ticket['title']) ?></dd>
            <dt class="col-sm-3">Category:</dt>
            <dd class="col-sm-9"><?= e($ticket['category_name']) ?></dd>
            <dt class="col-sm-3">Priority:</dt>
            <dd class="col-sm-9"><?= e($ticket['priority_name']) ?></dd>
            <dt class="col-sm-3">Status:</dt>
            <dd class="col-sm-9">
                <span class="badge <?= status_badge($ticket['status']) ?>">
                     <?= e($ticket['status']) ?>
                </span>
            </dd> 
            <dt class="col-sm-3">Creator:</dt>
            <dd class="col-sm-9"><?= e($ticket['creator_name']) ?></dd>
            <dt class="col-sm-3">Assignee:</dt>
            <dd class="col-sm-9"><?= e($ticket['assigned_to'] ?? '—') ?></dd>
            <dt class="col-sm-3">Due Date:</dt>
            <dd class="col-sm-9"><?= nl2br(e($ticket['due_at'])) ?></dd>
            <dt class="col-sm-3">Description:</dt>
            <dd class="col-sm-9"><?= e($ticket['description']) ?></dd>
            <dt class="col-sm-3">Created At:</dt>
            <dd class="col-sm-9"><?= e($ticket['created_at']) ?></dd>
            <dt class="col-sm-3">Resolved At:</dt>
            <dd class="col-sm-9"><?= e($ticket['resolved_at'] ?? '—') ?></dd>
            <dt class="col-sm-3">SLA State:</dt>
            <dd class="col-sm-9">
                <span class="badge <?= sla_badge($ticket['sla_state']) ?>">
                     <?= e($ticket['sla_state']) ?>
                </span>     
            </dd>
        </dl>    
        <!-- ===== TIMELINE (list-group) ===== -->
        <h2 class="mb-3">Ticket History</h2>
        <ul class="list-group">
            <?php if (!$updates): ?>
                <li class="list-group-item">No updates yet</li>
            <?php endif; ?>

            <?php foreach ($updates as $u): ?>
                <li class="list-group-item">
                <div class="d-flex w-100 justify-content-between">
                    <h5 class="mb-1"><?= e($u['author_name']) ?></h5>
                    <small><?= e($u['created_at']) ?></small>
                </div>
            <?php if ($u['comment'] !== null): ?>
                <p class="mb-1"><?= e($u['comment']) ?></p>
            <?php endif; ?>
                </li>
                <?php if ($u['new_status'] !== null): ?>
                    <li class="list-group-item">Status change: <?= e($u['old_status']) ?> -> 
                        <?= e($u['new_status']) ?></li>
                <?php endif; ?> 
            <?php endforeach; ?>
         </ul>
        <!-- ===== ADD COMMENT FORM ===== -->
    <?php if ($ticket['status'] !== 'Resolved'): ?>
    <div class="card mt-3">
        <div class="card-body">
        <h5>Add Comment</h5>
        <form method="post" action="update.php">
            <?= csrf_field() ?>
            <input type="hidden" name="ticket_id" 
                value="<?= e($ticket['ticket_id']) ?>">
            <input type="hidden" name="action" value="comment">

            <textarea name="comment" class="form-control" rows="3" 
                required></textarea>
            
            <button type="submit" class="btn btn-primary m-2">Submit</button>
        </form>
        </div>
    </div>
    <?php endif; ?>  

    <div class="col-lg-4">
        <!-- ===== STATUS CHANGE FORM (next status only — BR16) ===== -->
        <!-- ===== ASSIGN FORM (Admin only — BR17) ===== -->
    </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>

