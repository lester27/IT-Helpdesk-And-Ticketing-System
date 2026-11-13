<?php
/**
 * reports/index.php — management reports (Admin)
 * OWNER: Lauren (page layout) · Myrson (queries)
 *
 * Use Bootstrap nav-tabs, one tab per report. Copy queries EXACTLY from
 * database/03_reports.sql so the paper and the app match.
 *
 *   Tab 1  SLA Overview          — v_ticket_sla (R5), counts per sla_state + table
 *   Tab 2  Technician Workload   — LEFT OUTER JOIN (R2)
 *   Tab 3  Recurring Equipment   — INTERSECT (R3), equipment under warranty w/ >= 3 tickets / 90 days
 *   Tab 4  Slower than Average   — subquery (R4)
 *   (Ticket list R1 lives in tickets/index.php)
 *
 * Nice to have: "Print" button (window.print()) for the paper's screenshots.
 */
require_once __DIR__ . '/../includes/auth.php';
require_role(['Admin']);

// TODO(Reports): run R2, R3, R4, R5
$sla = $workload = $recurring = $slow = [];

$pageTitle = 'Reports';
require __DIR__ . '/../includes/header.php';
?>

<h1 class="h3 mb-3">Reports</h1>

<!-- ===== TABS ===== -->
<!-- TODO(Reports): ul.nav.nav-tabs + div.tab-content with 4 panes -->

<?php require __DIR__ . '/../includes/footer.php'; ?>

