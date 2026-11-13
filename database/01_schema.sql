-- =====================================================================
-- IT Helpdesk / Ticketing System — SCHEMA
-- COMP 018 Database Administration · BSIT 3-5 · PUP CCIS
--
-- Target: MySQL 8.0.31+  or  MariaDB 10.4+ (XAMPP default)
-- Run order: 01_schema.sql -> 02_seed.sql -> 03_reports.sql (optional)
-- Re-running this file DROPS and recreates the whole database.
-- Source of truth: docs/project-paper.md (Data Dictionary) and docs/erd.png
-- =====================================================================

DROP DATABASE IF EXISTS it_helpdesk;
CREATE DATABASE it_helpdesk CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE it_helpdesk;

-- ---------------------------------------------------------------------
-- LOOKUP TABLES
-- ---------------------------------------------------------------------

CREATE TABLE departments (
    department_id   INT          NOT NULL AUTO_INCREMENT,
    department_name VARCHAR(100) NOT NULL,
    CONSTRAINT pk_departments PRIMARY KEY (department_id),
    CONSTRAINT uq_departments_name UNIQUE (department_name)
) ENGINE=InnoDB;

CREATE TABLE categories (
    category_id   INT         NOT NULL AUTO_INCREMENT,
    category_name VARCHAR(50) NOT NULL,
    CONSTRAINT pk_categories PRIMARY KEY (category_id),
    CONSTRAINT uq_categories_name UNIQUE (category_name)
) ENGINE=InnoDB;

-- BR9: each priority has one resolution target in hours (> 0)
CREATE TABLE priorities (
    priority_id      INT         NOT NULL AUTO_INCREMENT,
    priority_name    VARCHAR(20) NOT NULL,
    resolution_hours INT         NOT NULL,
    CONSTRAINT pk_priorities PRIMARY KEY (priority_id),
    CONSTRAINT uq_priorities_name UNIQUE (priority_name),
    CONSTRAINT chk_priorities_hours CHECK (resolution_hours > 0)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- MASTER TABLES
-- ---------------------------------------------------------------------

-- BR1 one role · BR2 one department · BR3 unique username/email · BR4 deactivate, never delete
CREATE TABLE users (
    user_id       INT          NOT NULL AUTO_INCREMENT,
    department_id INT          NOT NULL,
    username      VARCHAR(50)  NOT NULL,
    email         VARCHAR(100) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name     VARCHAR(100) NOT NULL,
    role          VARCHAR(20)  NOT NULL,
    is_active     BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_users PRIMARY KEY (user_id),
    CONSTRAINT uq_users_username UNIQUE (username),
    CONSTRAINT uq_users_email UNIQUE (email),
    CONSTRAINT chk_users_role CHECK (role IN ('Employee', 'Technician', 'Admin')),
    CONSTRAINT fk_users_department FOREIGN KEY (department_id)
        REFERENCES departments (department_id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Junction: which categories each Technician is qualified for (composite PK, no AUTO_INCREMENT)
CREATE TABLE technician_skills (
    user_id     INT       NOT NULL,
    category_id INT       NOT NULL,
    added_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_technician_skills PRIMARY KEY (user_id, category_id),
    CONSTRAINT fk_skills_user FOREIGN KEY (user_id)
        REFERENCES users (user_id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_skills_category FOREIGN KEY (category_id)
        REFERENCES categories (category_id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- BR12 unique serial + status · BR13 at most one current holder
CREATE TABLE equipment (
    equipment_id     INT         NOT NULL AUTO_INCREMENT,
    equipment_type   VARCHAR(50) NOT NULL,
    brand            VARCHAR(50) NOT NULL,
    model            VARCHAR(50) NOT NULL,
    serial_number    VARCHAR(50) NOT NULL,
    status           VARCHAR(20) NOT NULL DEFAULT 'In Stock',
    warranty_expiry  DATE        NULL,
    assigned_user_id INT         NULL,
    CONSTRAINT pk_equipment PRIMARY KEY (equipment_id),
    CONSTRAINT uq_equipment_serial UNIQUE (serial_number),
    CONSTRAINT chk_equipment_status CHECK (status IN ('In Stock', 'Assigned', 'Under Repair', 'Retired')),
    CONSTRAINT fk_equipment_user FOREIGN KEY (assigned_user_id)
        REFERENCES users (user_id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- TRANSACTION TABLES
-- ---------------------------------------------------------------------

-- BR5 required fields · BR6 status + default Open · BR7 optional assignee/equipment
-- due_at is filled by trigger trg_tickets_bi (BR10). Do NOT set it from PHP.
CREATE TABLE tickets (
    ticket_id    INT          NOT NULL AUTO_INCREMENT,
    title        VARCHAR(255) NOT NULL,
    description  TEXT         NOT NULL,
    category_id  INT          NOT NULL,
    priority_id  INT          NOT NULL,
    status       VARCHAR(20)  NOT NULL DEFAULT 'Open',
    created_by   INT          NOT NULL,
    assigned_to  INT          NULL,
    equipment_id INT          NULL,
    created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    due_at       TIMESTAMP    NOT NULL,
    resolved_at  TIMESTAMP    NULL DEFAULT NULL,
    CONSTRAINT pk_tickets PRIMARY KEY (ticket_id),
    CONSTRAINT chk_tickets_status CHECK (status IN ('Open', 'In Progress', 'Resolved')),
    CONSTRAINT fk_tickets_category FOREIGN KEY (category_id)
        REFERENCES categories (category_id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_tickets_priority FOREIGN KEY (priority_id)
        REFERENCES priorities (priority_id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_tickets_created_by FOREIGN KEY (created_by)
        REFERENCES users (user_id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_tickets_assigned_to FOREIGN KEY (assigned_to)
        REFERENCES users (user_id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_tickets_equipment FOREIGN KEY (equipment_id)
        REFERENCES equipment (equipment_id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- BR8: every status change and comment. Plain comments leave both status fields NULL.
CREATE TABLE ticket_updates (
    update_id  INT         NOT NULL AUTO_INCREMENT,
    ticket_id  INT         NOT NULL,
    user_id    INT         NOT NULL,
    old_status VARCHAR(20) NULL,
    new_status VARCHAR(20) NULL,
    comment    TEXT        NULL,
    created_at TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_ticket_updates PRIMARY KEY (update_id),
    CONSTRAINT chk_updates_old_status CHECK (old_status IN ('Open', 'In Progress', 'Resolved')),
    CONSTRAINT chk_updates_new_status CHECK (new_status IN ('Open', 'In Progress', 'Resolved')),
    CONSTRAINT fk_updates_ticket FOREIGN KEY (ticket_id)
        REFERENCES tickets (ticket_id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_updates_user FOREIGN KEY (user_id)
        REFERENCES users (user_id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- INDEXES (transaction tables grow large; FK columns already get indexes)
-- ---------------------------------------------------------------------
CREATE INDEX idx_tickets_status      ON tickets (status);
CREATE INDEX idx_tickets_due_at      ON tickets (due_at);
CREATE INDEX idx_tickets_created_at  ON tickets (created_at);
CREATE INDEX idx_updates_created_at  ON ticket_updates (created_at);

-- ---------------------------------------------------------------------
-- TRIGGER — BR10: due_at = created_at + priority.resolution_hours
-- ---------------------------------------------------------------------
DELIMITER $$

CREATE TRIGGER trg_tickets_bi
BEFORE INSERT ON tickets
FOR EACH ROW
BEGIN
    DECLARE v_hours INT;

    SELECT resolution_hours INTO v_hours
    FROM priorities
    WHERE priority_id = NEW.priority_id;

    IF NEW.created_at IS NULL THEN
        SET NEW.created_at = CURRENT_TIMESTAMP;
    END IF;

    SET NEW.due_at = NEW.created_at + INTERVAL v_hours HOUR;
END$$

DELIMITER ;

-- ---------------------------------------------------------------------
-- VIEW — BR11: SLA state per ticket
--   Unresolved: Breached if past due, At Risk if < 20% of target left, else On Track
--   Resolved:   Met if resolved_at <= due_at, else Breached
--   overdue_hours: hours past due_at, up to NOW() or up to resolved_at (0 if not late)
-- ---------------------------------------------------------------------
CREATE OR REPLACE VIEW v_ticket_sla AS
SELECT
    t.ticket_id,
    t.title,
    t.status,
    t.priority_id,
    t.assigned_to,
    t.created_at,
    t.due_at,
    t.resolved_at,
    CASE
        WHEN t.status = 'Resolved' AND t.resolved_at <= t.due_at THEN 'Met'
        WHEN t.status = 'Resolved'                               THEN 'Breached'
        WHEN NOW() > t.due_at                                    THEN 'Breached'
        WHEN TIMESTAMPDIFF(SECOND, NOW(), t.due_at)
             < 0.2 * TIMESTAMPDIFF(SECOND, t.created_at, t.due_at) THEN 'At Risk'
        ELSE 'On Track'
    END AS sla_state,
    ROUND(GREATEST(0,
        TIMESTAMPDIFF(MINUTE, t.due_at, COALESCE(t.resolved_at, NOW())) / 60
    ), 2) AS overdue_hours
FROM tickets t;

