<?php
/**
 * tickets/assign.php — POST handler: Admin assigns a Technician (BR17)
 * OWNER: Lester
 *
 * POST: csrf, ticket_id, technician_id
 *
 * Checks (all must pass):
 *   - current user is Admin
 *   - ticket exists and status <> 'Resolved' (BR18)
 *   - technician: role = 'Technician' AND is_active = TRUE
 *   - technician is qualified: row exists in technician_skills (user_id, ticket.category_id)
 *     If not, show error "Add the qualification first" with link to users/skills.php
 *
 * Then: UPDATE tickets SET assigned_to = ?
 *       INSERT ticket_updates comment "Assigned to <name>." (status fields NULL)
 *       — do both in one transaction.
 *
 * Query for the dropdown in view.php (qualified, active technicians + their open load):
 *   SELECT u.user_id, u.full_name, COUNT(t.ticket_id) AS open_tickets
 *   FROM users u
 *   JOIN technician_skills s ON s.user_id = u.user_id AND s.category_id = :category
 *   LEFT JOIN tickets t ON t.assigned_to = u.user_id AND t.status <> 'Resolved'
 *   WHERE u.role = 'Technician' AND u.is_active = TRUE
 *   GROUP BY u.user_id, u.full_name ORDER BY open_tickets
 */
require_once __DIR__ . '/../includes/auth.php';
require_role(['Admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/tickets/index.php');
}
verify_csrf();

$ticketId = (int)($_POST['ticket_id'] ?? 0);

// TODO(Tickets): implement checks + transaction

redirect(BASE_URL . '/tickets/view.php?id=' . $ticketId);

