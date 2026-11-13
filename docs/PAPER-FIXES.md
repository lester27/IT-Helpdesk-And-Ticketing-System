# Paper Fixes

Issues still open in `project-paper.md` after the latest update. The repo (SQL, PHP comments, docs) already uses the **new BR numbering**: BR1–BR15 in the database, BR16–BR19 in the application.

## 1. BR references in the data dictionary
Equipment rows still use the old numbers:

| Column | Paper says | Should be |
|---|---|---|
| `equipment.serial_number` | BR12 | BR13 |
| `equipment.status` | BR12 | BR13 |
| `equipment.assigned_user_id` | BR13 | BR14 |

The `priorities`, `technician_skills`, `users`, `tickets.status` and `tickets.due_at` references already match the new numbering.

## 2. Missing ERD
- The "Entity Relationship Diagram" heading is empty in the MD export. Insert `docs/erd.png` (in the docx).

## 3. Wording
- Purpose says "Building the database is not required yet". Update it now that the system is being built.
- BR12 says "met" but the view returns "Met". Keep the capitalization consistent.
- The new BR5 heading is preceded by empty `####` headings (export artifacts). Delete them.

## 4. Technical notes worth adding to the paper
- `INTERSECT` needs MySQL **8.0.31+** or MariaDB **10.3+**. XAMPP ships MariaDB, which supports it.
- `CHECK` constraints are enforced only on MySQL 8.0.16+ / MariaDB 10.2+.
- Priority hours used in the seed: Low 72, Medium 48, High 24, Urgent 4. Confirm as a team.
