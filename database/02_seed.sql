-- =====================================================================
-- IT Helpdesk — SEED DATA (for development and demo)
-- Run AFTER 01_schema.sql.
--
-- All seeded accounts use the password:  password
-- (bcrypt hash below is the well-known hash of "password" — DEV ONLY)
--
-- Ticket times are relative to NOW() so the SLA view always shows a mix
-- of On Track / At Risk / Breached / Met whenever you re-seed.
-- =====================================================================
USE it_helpdesk;

-- ---------- Lookups ----------
INSERT INTO departments (department_name) VALUES
('Information Technology'),   -- 1
('Human Resources'),          -- 2
('Finance'),                  -- 3
('Sales and Marketing'),      -- 4
('Operations');               -- 5

INSERT INTO categories (category_name) VALUES
('Hardware'),        -- 1
('Software'),        -- 2
('Network'),         -- 3
('Account Access'),  -- 4
('Other');           -- 5

-- TODO(team): confirm the resolution targets with the group
INSERT INTO priorities (priority_name, resolution_hours) VALUES
('Low',    72),  -- 1
('Medium', 48),  -- 2
('High',   24),  -- 3
('Urgent',  4);  -- 4

-- ---------- Users ----------
SET @pw = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

INSERT INTO users (department_id, username, email, password_hash, full_name, role, is_active) VALUES
(1, 'admin',     'admin@company.test',     @pw, 'Alex Admin',       'Admin',      TRUE),  -- 1
(1, 'tech.ramos','ramos@company.test',     @pw, 'Rico Ramos',       'Technician', TRUE),  -- 2
(1, 'tech.lim',  'lim@company.test',       @pw, 'Grace Lim',        'Technician', TRUE),  -- 3
(1, 'tech.cruz', 'cruz@company.test',      @pw, 'Ben Cruz',         'Technician', TRUE),  -- 4 (no open tickets -> shows in outer join)
(1, 'tech.old',  'old.tech@company.test',  @pw, 'Former Technician','Technician', FALSE), -- 5 (deactivated, BR4)
(2, 'maria',     'maria@company.test',     @pw, 'Maria Santos',     'Employee',   TRUE),  -- 6
(3, 'juan',      'juan@company.test',      @pw, 'Juan Dela Cruz',   'Employee',   TRUE),  -- 7
(4, 'ana',       'ana@company.test',       @pw, 'Ana Reyes',        'Employee',   TRUE),  -- 8
(5, 'paolo',     'paolo@company.test',     @pw, 'Paolo Garcia',     'Employee',   TRUE),  -- 9
(3, 'liza',      'liza@company.test',      @pw, 'Liza Mendoza',     'Employee',   TRUE);  -- 10

-- ---------- Technician skills ----------
INSERT INTO technician_skills (user_id, category_id) VALUES
(2, 1), (2, 3),          -- Ramos: Hardware, Network
(3, 2), (3, 4), (3, 5),  -- Lim: Software, Account Access, Other
(4, 1), (4, 2),          -- Cruz: Hardware, Software
(5, 3);                  -- Former tech: Network

-- ---------- Equipment ----------
INSERT INTO equipment (equipment_type, brand, model, serial_number, status, warranty_expiry, assigned_user_id) VALUES
('Laptop',  'Lenovo', 'ThinkPad E14',   'LNV-E14-0001', 'Assigned',     CURDATE() + INTERVAL 300 DAY, 6),  -- 1 recurring + warranty -> INTERSECT hit
('Laptop',  'Dell',   'Latitude 3420',  'DEL-3420-0002','Assigned',     CURDATE() - INTERVAL 30 DAY,  7),  -- 2 recurring but warranty expired
('Laptop',  'HP',     'ProBook 440',    'HP-440-0003',  'Assigned',     CURDATE() + INTERVAL 500 DAY, 8),  -- 3
('Printer', 'Epson',  'L3210',          'EPS-L3210-004','Under Repair', CURDATE() + INTERVAL 100 DAY, NULL), -- 4
('Router',  'TP-Link','Archer C6',      'TPL-C6-0005',  'In Stock',     NULL,                         NULL), -- 5
('Desktop', 'Acer',   'Veriton X',      'ACR-VX-0006',  'Retired',      CURDATE() - INTERVAL 400 DAY, NULL), -- 6 retired (BR19)
('Laptop',  'Lenovo', 'IdeaPad 3',      'LNV-IP3-0007', 'Assigned',     CURDATE() + INTERVAL 200 DAY, 9);  -- 7

-- ---------- Tickets ----------
-- created_at is given explicitly; trigger trg_tickets_bi computes due_at.
-- Statuses that are Resolved also get resolved_at.
INSERT INTO tickets (title, description, category_id, priority_id, status, created_by, assigned_to, equipment_id, created_at, resolved_at) VALUES
-- Equipment 1 (under warranty): 3 tickets in 90 days -> recurring + warranty
('Laptop will not power on',        'ThinkPad shows no lights when plugged in.',        1, 3, 'Resolved',    6, 2, 1, NOW() - INTERVAL 60 DAY,  NOW() - INTERVAL 60 DAY + INTERVAL 10 HOUR),
('Laptop overheating',              'Fan is loud and laptop shuts down after 30 min.', 1, 2, 'Resolved',    6, 4, 1, NOW() - INTERVAL 30 DAY,  NOW() - INTERVAL 30 DAY + INTERVAL 60 HOUR),
('Laptop battery not charging',     'Battery stuck at 0%.',                            1, 2, 'In Progress', 6, 2, 1, NOW() - INTERVAL 40 HOUR, NULL),
-- Equipment 2 (warranty expired): 3 tickets -> recurring but NOT in INTERSECT
('Keyboard keys not working',       'Several keys unresponsive.',                       1, 1, 'Resolved',    7, 4, 2, NOW() - INTERVAL 50 DAY,  NOW() - INTERVAL 50 DAY + INTERVAL 20 HOUR),
('Screen flickering',               'Display flickers intermittently.',                1, 2, 'Resolved',    7, 2, 2, NOW() - INTERVAL 20 DAY,  NOW() - INTERVAL 20 DAY + INTERVAL 30 HOUR),
('Trackpad unresponsive',           'Trackpad stopped responding after update.',       1, 3, 'Open',        7, NULL, 2, NOW() - INTERVAL 2 HOUR, NULL),
-- Assorted tickets for SLA states
('Cannot log in to email',          'Password reset needed for Outlook.',              4, 4, 'Resolved',    8, 3, NULL, NOW() - INTERVAL 5 DAY, NOW() - INTERVAL 5 DAY + INTERVAL 2 HOUR),   -- Met
('Accounting software crashes',     'App crashes when exporting reports.',             2, 3, 'In Progress', 10, 3, NULL, NOW() - INTERVAL 30 HOUR, NULL),                               -- Breached (24h target)
('Printer paper jam',               'Second floor printer jams every print.',          1, 1, 'Open',        9, NULL, 4, NOW() - INTERVAL 60 HOUR, NULL),                                -- At Risk (72h target, 12h left)
('No internet on 3rd floor',        'All PCs on 3rd floor lost connection.',           3, 4, 'In Progress', 9, 2, NULL, NOW() - INTERVAL 1 HOUR, NULL),                                -- On Track
('Request new software license',    'Need Adobe Acrobat license.',                     2, 1, 'Open',        8, NULL, NULL, NOW() - INTERVAL 3 HOUR, NULL),                              -- On Track
('VPN not connecting',              'VPN client times out.',                           3, 2, 'Resolved',    10, 2, NULL, NOW() - INTERVAL 10 DAY, NOW() - INTERVAL 10 DAY + INTERVAL 70 HOUR), -- Breached (resolved late)
('Account locked after reset',      'Locked out after too many attempts.',             4, 3, 'Open',        7, 3, NULL, NOW() - INTERVAL 4 HOUR, NULL),
('Monitor request for new hire',    'New hire needs a second monitor.',                5, 1, 'Open',        6, NULL, NULL, NOW() - INTERVAL 1 DAY, NULL);

-- ---------- Ticket updates (history) ----------
INSERT INTO ticket_updates (ticket_id, user_id, old_status, new_status, comment, created_at) VALUES
(1, 1, NULL,          NULL,          'Assigned to Rico Ramos.',                         NOW() - INTERVAL 60 DAY + INTERVAL 1 HOUR),
(1, 2, 'Open',        'In Progress', 'Checking power adapter and board.',               NOW() - INTERVAL 60 DAY + INTERVAL 2 HOUR),
(1, 2, 'In Progress', 'Resolved',    'Replaced faulty power adapter.',                  NOW() - INTERVAL 60 DAY + INTERVAL 10 HOUR),
(2, 4, 'Open',        'In Progress', NULL,                                              NOW() - INTERVAL 30 DAY + INTERVAL 5 HOUR),
(2, 4, 'In Progress', 'Resolved',    'Cleaned fan, reapplied thermal paste.',           NOW() - INTERVAL 30 DAY + INTERVAL 60 HOUR),
(3, 2, 'Open',        'In Progress', 'Battery health 12%. Possible warranty claim.',    NOW() - INTERVAL 38 HOUR),
(7, 3, 'Open',        'In Progress', NULL,                                              NOW() - INTERVAL 5 DAY + INTERVAL 30 MINUTE),
(7, 3, 'In Progress', 'Resolved',    'Password reset and MFA re-enrolled.',             NOW() - INTERVAL 5 DAY + INTERVAL 2 HOUR),
(8, 3, 'Open',        'In Progress', 'Reproduced crash, checking vendor patch.',        NOW() - INTERVAL 28 HOUR),
(8, 10, NULL,         NULL,          'Still happening this morning, please prioritize.',NOW() - INTERVAL 2 HOUR),
(10, 2, 'Open',       'In Progress', 'Switch on 3rd floor is down, replacing.',         NOW() - INTERVAL 30 MINUTE);

