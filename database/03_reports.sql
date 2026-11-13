-- =====================================================================
-- IT Helpdesk — REPORTING QUERIES
-- These are the queries named in the paper (Core Database Features).
-- The PHP report pages (reports/*.php) should run these same queries.
-- =====================================================================
USE it_helpdesk;

-- ---------------------------------------------------------------------
-- R1. TICKET LIST — INNER JOIN across tickets, users, categories, priorities
-- ---------------------------------------------------------------------
SELECT
    t.ticket_id,
    t.title,
    c.category_name,
    p.priority_name,
    t.status,
    u.full_name  AS created_by,
    t.created_at,
    t.due_at
FROM tickets t
INNER JOIN users      u ON u.user_id     = t.created_by
INNER JOIN categories c ON c.category_id = t.category_id
INNER JOIN priorities p ON p.priority_id = t.priority_id
ORDER BY t.created_at DESC;

-- ---------------------------------------------------------------------
-- R2. TECHNICIAN WORKLOAD — LEFT OUTER JOIN
-- Technicians with zero open tickets still appear (open_tickets = 0).
-- The status filter is in the ON clause on purpose; putting it in WHERE
-- would turn this back into an inner join.
-- ---------------------------------------------------------------------
SELECT
    u.user_id,
    u.full_name,
    COUNT(t.ticket_id) AS open_tickets
FROM users u
LEFT OUTER JOIN tickets t
       ON t.assigned_to = u.user_id
      AND t.status <> 'Resolved'
WHERE u.role = 'Technician'
  AND u.is_active = TRUE
GROUP BY u.user_id, u.full_name
ORDER BY open_tickets ASC, u.full_name;

-- ---------------------------------------------------------------------
-- R3. RECURRING EQUIPMENT STILL UNDER WARRANTY — INTERSECT (BR15)
-- Equipment with >= 3 tickets in the last 90 days  INTERSECT  equipment under warranty.
-- Requires MySQL 8.0.31+ or MariaDB 10.3+.
-- ---------------------------------------------------------------------
SELECT t.equipment_id
FROM tickets t
WHERE t.equipment_id IS NOT NULL
  AND t.created_at >= NOW() - INTERVAL 90 DAY
GROUP BY t.equipment_id
HAVING COUNT(*) >= 3

INTERSECT

SELECT e.equipment_id
FROM equipment e
WHERE e.warranty_expiry >= CURDATE();

-- R3 (display version) — same INTERSECT wrapped to show equipment details
SELECT e.equipment_id, e.equipment_type, e.brand, e.model, e.serial_number, e.warranty_expiry
FROM equipment e
WHERE e.equipment_id IN (
    SELECT t.equipment_id
    FROM tickets t
    WHERE t.equipment_id IS NOT NULL
      AND t.created_at >= NOW() - INTERVAL 90 DAY
    GROUP BY t.equipment_id
    HAVING COUNT(*) >= 3
    INTERSECT
    SELECT e2.equipment_id
    FROM equipment e2
    WHERE e2.warranty_expiry >= CURDATE()
);

-- ---------------------------------------------------------------------
-- R4. TICKETS SLOWER THAN AVERAGE — SUBQUERY
-- Resolution time in hours, for resolved tickets only.
-- ---------------------------------------------------------------------
SELECT
    t.ticket_id,
    t.title,
    ROUND(TIMESTAMPDIFF(MINUTE, t.created_at, t.resolved_at) / 60, 2) AS resolution_hours
FROM tickets t
WHERE t.status = 'Resolved'
  AND TIMESTAMPDIFF(MINUTE, t.created_at, t.resolved_at) > (
        SELECT AVG(TIMESTAMPDIFF(MINUTE, t2.created_at, t2.resolved_at))
        FROM tickets t2
        WHERE t2.status = 'Resolved'
  )
ORDER BY resolution_hours DESC;

-- ---------------------------------------------------------------------
-- R5. SLA OVERVIEW — from view v_ticket_sla (BR12)
-- ---------------------------------------------------------------------
SELECT ticket_id, title, status, due_at, sla_state, overdue_hours
FROM v_ticket_sla
ORDER BY FIELD(sla_state, 'Breached', 'At Risk', 'On Track', 'Met'), due_at;

