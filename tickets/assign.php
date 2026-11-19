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
$technicianId = (int)($_POST['technician_id'] ?? 0);

$sql = "SELECT t.ticket_id, t.status, t.category_id
        FROM tickets t
        WHERE ticket_id = ?";

$stmt = db()->prepare($sql);
$stmt->execute([$ticketId]);
$ticket = $stmt->fetch();

if (!$ticket) {
    redirect(BASE_URL . '/tickets/index.php');
    exit;
}

if ($ticket['status'] === 'Resolved') {
    flash('danger', 'Resolved tickets cannot be assigned');
    redirect(BASE_URL . '/tickets/view.php?id=' . $ticketId);
    exit;
}

$sql = "SELECT u.user_id, u.full_name
        FROM users u
        WHERE user_id = ? AND role = 'Technician' AND is_active = TRUE";

$stmt = db()->prepare($sql);
$stmt->execute([$technicianId]);
$tech = $stmt->fetch();

if (!$tech) {
    flash('danger', 'Inactive or invalid technician');
    redirect(BASE_URL . '/tickets/view.php?id=' . $ticketId);
    exit;
}

$sql = "SELECT *
        FROM technician_skills
        WHERE user_id = ? AND category_id = ?";

$stmt = db()->prepare($sql);
$stmt->execute([$technicianId, $ticket['category_id']]);

if(!$stmt->fetch()) {
    flash('danger', 'Technician is not qualified. Add the qualification first in Skills');
    redirect(BASE_URL . '/tickets/view.php?id=' . $ticketId);
}

db()->beginTransaction();
try {
    $sql = "UPDATE tickets SET assigned_to = ? WHERE ticket_id = ?";
    $stmt = db()->prepare($sql);
    $stmt->execute([$technicianId, $ticketId]);

    $sql = "INSERT INTO ticket_updates (ticket_id, user_id, old_status, new_status, comment)
        VALUES (?, ?, NULL, NULL, ?)";

    $stmt = db()->prepare($sql);
    $stmt->execute([$ticketId, current_user()['user_id'], 'Assigned to ' . $tech['full_name']]);
    db()->commit();
    flash('success', 'Assigned to ' . $tech['full_name']);
} catch (Throwable $e) {
    db()->rollBack();
    throw $e;
}



redirect(BASE_URL . '/tickets/view.php?id=' . $ticketId);

