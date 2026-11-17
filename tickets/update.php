<?php
/**
 * tickets/update.php — POST handler: add comment OR change status
 * OWNER: Lester
 * No HTML output; always redirects back to view.php?id=N
 *
 * POST: csrf, ticket_id, action ('comment' | 'status'), comment, new_status
 *
 * Common checks:
 *   - ticket exists; if status = 'Resolved' -> reject (BR18, resolved is final)
 *
 * action = 'comment':
 *   INSERT ticket_updates (ticket_id, user_id, old_status=NULL, new_status=NULL, comment)   (BR9)
 *
 * action = 'status' (assigned Technician or Admin only):
 *   - Only allow the NEXT status (BR16): Open -> In Progress -> Resolved. No skipping, no going back.
 *   - Use a TRANSACTION:
 *       UPDATE tickets SET status = ?, resolved_at = (NOW() if new = 'Resolved') WHERE ticket_id = ?
 *       INSERT ticket_updates (ticket_id, user_id, old_status, new_status, comment)
 *     commit; rollback on exception.
 */
require_once __DIR__ . '/../includes/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/tickets/index.php');
}
verify_csrf();

$sql = "SELECT t.ticket_id, t.status, t.created_by, t.assigned_to
        FROM tickets t
        WHERE t.ticket_id = ?";

$ticketId = (int)($_POST['ticket_id'] ?? 0);
$action   = $_POST['action'] ?? '';

$stmt = db()->prepare($sql);
$stmt->execute([$ticketId]);
$ticket = $stmt->fetch();
$nextStatusMap = [
    'Open' => 'In Progress',
    'In Progress' => 'Resolved'
];

if (!$ticket) {
    redirect(BASE_URL . '/tickets/index.php');
}

if ($ticket['status'] === 'Resolved') {
    flash('danger', 'You cannot update a resolved ticket');
    redirect(BASE_URL . '/tickets/view.php?id=' . $ticketId);
}

if (has_role('Employee') && current_user()['user_id'] !== $ticket['created_by']) {
    http_response_code(403);
    require __DIR__ . '/../errors/403.php';
    exit;
}

if ($action === 'comment') {
    $comment = trim($_POST['comment'] ?? '');
    if($comment === '') {
        flash('danger', 'Comment cannot be empty');
        redirect(BASE_URL . '/tickets/view.php?id=' . $ticketId);
    } else {
        $sql = "INSERT INTO ticket_updates (ticket_id, user_id, old_status, new_status, comment)
                VALUES (?, ?, NULL, NULL, ?)";
        $stmt = db()->prepare($sql);
        $stmt -> execute([$ticketId, current_user()['user_id'], $comment]);
        flash ('success', 'Comment Added');
    }
}

if ($action === 'status'){
    $canChangeStatus = (has_role('Technician') && 
        $ticket['assigned_to'] === current_user()['user_id']) || has_role('Admin');
        
    if (!$canChangeStatus){
        http_response_code(403);
        require __DIR__ . '/../errors/403.php';
        exit;
    }

    $newStatus = $_POST['new_status'] ?? '';
    $nextStatus = $nextStatusMap[$ticket['status']] ?? null;

    if ($newStatus !== $nextStatus) {
        flash('danger', 'Invalid status change');
        redirect(BASE_URL . '/tickets/view.php?id=' . $ticketId);
    }

    $comment = trim($_POST['comment'] ?? '');
    if ($comment === '') {
        $comment = null;
    }

    db()->beginTransaction();
    try{
        if ($newStatus === 'Resolved') {
            $sql = "UPDATE tickets SET status = ?, resolved_at = NOW() WHERE ticket_id = ?";
        } else {
            $sql = "UPDATE tickets SET status = ? WHERE ticket_id = ?";
        }

        $stmt = db()->prepare($sql);
        $stmt -> execute([$newStatus, $ticketId]);

        $sql = "INSERT INTO ticket_updates (ticket_id, user_id, old_status, new_status, comment)
                VALUES (?, ?, ?, ?, ?)";

        $stmt = db()->prepare($sql);
        $stmt -> execute([$ticketId, current_user()['user_id'], $ticket['status'], $newStatus, $comment]);
        db()->commit();
        flash ('success', 'Status updated');
    } catch (Throwable $e) {
        db()->rollBack(); 
        throw $e;
    } 
}

redirect(BASE_URL . '/tickets/view.php?id=' . $ticketId);

