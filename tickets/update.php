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
 *   INSERT ticket_updates (ticket_id, user_id, old_status=NULL, new_status=NULL, comment)   (BR8)
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

$ticketId = (int)($_POST['ticket_id'] ?? 0);
$action   = $_POST['action'] ?? '';

// TODO(Tickets): implement the rules above

redirect(BASE_URL . '/tickets/view.php?id=' . $ticketId);

