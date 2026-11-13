<?php
/**
 * dashboard.php — role-based landing page
 * OWNER: Lauren (page layout) · Myrson (queries)
 *
 * EMPLOYEE   : count of my Open / In Progress / Resolved tickets,
 *              table of my unresolved tickets with due_at + SLA badge (v_ticket_sla)
 * TECHNICIAN : my assigned unresolved tickets sorted by due_at,
 *              counts of my At Risk / Breached tickets
 * ADMIN      : unassigned tickets count (assigned_to IS NULL),
 *              At Risk / Breached counts (v_ticket_sla),
 *              technician workload mini-table (reports query R2),
 *              recurring-equipment alert (reports query R3)
 */
require_once __DIR__ . '/includes/auth.php';
require_login();

$user = current_user();

// TODO: load data depending on $user['role']

$pageTitle = 'Dashboard';
require __DIR__ . '/includes/header.php';
?>

<h1 class="h3 mb-4">Welcome, <?= e($user['full_name']) ?></h1>

<?php if (has_role('Employee')): ?>
    <!-- ===== EMPLOYEE DASHBOARD ===== -->
    <!-- TODO: summary cards (Bootstrap .card in .row/.col) -->
    <!-- TODO: "My open tickets" table with status + SLA badges -->
    <!-- TODO: "Report a problem" button -> tickets/create.php -->

<?php elseif (has_role('Technician')): ?>
    <!-- ===== TECHNICIAN DASHBOARD ===== -->
    <!-- TODO: "Assigned to me" table sorted by due_at -->
    <!-- TODO: At Risk / Breached counters -->

<?php elseif (has_role('Admin')): ?>
    <!-- ===== ADMIN DASHBOARD ===== -->
    <!-- TODO: Unassigned / At Risk / Breached counter cards -->
    <!-- TODO: Technician workload table (R2) -->
    <!-- TODO: Recurring equipment under warranty alert (R3) -->
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>

