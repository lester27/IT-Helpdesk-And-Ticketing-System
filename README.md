# IT Helpdesk / Ticketing System for a Small Company

Final project for **COMP 018 – Database Administration** (combined with Web Development)
BSIT 3-5 · College of Computer and Information Sciences · Polytechnic University of the Philippines

**Proponents:** Beros, Jose Myrson · Cardona, Miguel · Flores, Hannah Lauren · Hernandez, Harold Benedict · Panganiban, Lester (Team Lead)

---

## What it does

A web-based helpdesk where:

- **Employees** report IT problems as tickets and track their status and due date.
- **Technicians** work on tickets assigned to them, change their status and leave comments.
- **Admins** assign tickets to qualified technicians, manage users and equipment, and view reports (SLA, workload, recurring equipment problems, slow tickets).

The database enforces data rules (keys, constraints, a due-date **trigger**, and an SLA **view**). The PHP application enforces workflow rules (status order, who can assign, locking resolved tickets). See [docs/project-paper.md](docs/project-paper.md) for the full design and business rules.

## Tech stack

| Layer     | Technology                                   |
|-----------|----------------------------------------------|
| Database  | MySQL 8.0.31+ / MariaDB 10.4+ (XAMPP)        |
| Backend   | PHP 8.0+ (plain PHP, PDO, no framework)      |
| Frontend  | HTML + **Bootstrap 5.3** (CSS) + vanilla JS  |
| Server    | Apache via XAMPP                             |

> Styling rule: **Bootstrap classes only**. `assets/css/style.css` is for tiny overrides.

## Setup (XAMPP)

1. Install [XAMPP](https://www.apachefriends.org/) (PHP 8.x). Start **Apache** and **MySQL**.
2. Clone into `htdocs`:
   ```bash
   cd C:\xampp\htdocs
   git clone <repo-url> IT-Helpdesk-DBA-Final-Project
   ```
3. Create the database. In phpMyAdmin (http://localhost/phpmyadmin) → **Import**, run in order:
   1. `database/01_schema.sql` — creates `it_helpdesk`, tables, trigger, view
   2. `database/02_seed.sql` — sample data

   Or from the command line:
   ```bash
   C:\xampp\mysql\bin\mysql -u root < database/01_schema.sql
   C:\xampp\mysql\bin\mysql -u root < database/02_seed.sql
   ```
4. Copy `config/db.example.php` to `config/db.php` and edit if your MySQL password isn't empty.
5. Open http://localhost/IT-Helpdesk-DBA-Final-Project
   (If you cloned into a different folder name, change `BASE_URL` in `includes/helpers.php`.)

### Demo accounts (password for all: `password`)

| Username     | Role       | Notes                                  |
|--------------|------------|----------------------------------------|
| `admin`      | Admin      |                                        |
| `tech.ramos` | Technician | Hardware, Network                      |
| `tech.lim`   | Technician | Software, Account Access, Other        |
| `tech.cruz`  | Technician | Hardware, Software — no open tickets   |
| `tech.old`   | Technician | **Deactivated** — login must fail      |
| `maria`, `juan`, `ana`, `paolo`, `liza` | Employee |                     |

> Re-running `01_schema.sql` **drops the database**. Run it whenever the schema changes, then re-seed.

## Project structure

```
├── index.php              Redirects to login or dashboard
├── login.php / logout.php
├── dashboard.php          Role-based landing page
├── tickets/               List, create, view, update (status/comment), assign
├── equipment/             List, create/edit, detail
├── users/                 User management + technician skills
├── departments/           Department management
├── reports/               SLA, workload, recurring equipment, slow tickets
├── errors/                403 page
├── includes/              db.php, auth.php, helpers.php, header/navbar/footer
├── config/                db.example.php (copy to db.php — gitignored)
├── assets/                css/style.css, js/app.js
├── database/              01_schema.sql, 02_seed.sql, 03_reports.sql
└── docs/                  Paper, ERD, roadmap, tasks
```

## Documentation

| Doc | What's inside |
|-----|---------------|
| [docs/project-paper.md](docs/project-paper.md) | Overview, business rules, data dictionary |
| [docs/erd.png](docs/erd.png) | Entity Relationship Diagram |
| [docs/ROADMAP.md](docs/ROADMAP.md) | 4-week schedule and milestones |
| [docs/TASKS.md](docs/TASKS.md) | Who owns what, week by week |
| [docs/GIT-GUIDE.md](docs/GIT-GUIDE.md) | **Read first:** branches, commits, pull requests, conflicts |
| [docs/PAGES.md](docs/PAGES.md) | Every page, who can access it, which rules it enforces |
| [docs/PAPER-FIXES.md](docs/PAPER-FIXES.md) | Inconsistencies found in the paper to fix |
| [CONTRIBUTING.md](CONTRIBUTING.md) | Git workflow and coding conventions |

## Business rules quick reference

| Enforced by **database** | Enforced by **PHP app** |
|---|---|
| BR1–BR4 users/departments (CHECK, FK, UNIQUE, RESTRICT) | BR16 status order Open → In Progress → Resolved |
| BR5–BR8 tickets (NOT NULL, FK, CHECK, DEFAULT) | BR17 only Admin assigns, only to active + qualified tech |
| BR9–BR10 due date (`trg_tickets_bi`) | BR18 resolved tickets are read-only |
| BR11 SLA state (`v_ticket_sla`) | BR19 retired equipment can't be assigned/linked |
| BR12–BR14 equipment + recurring report | |

