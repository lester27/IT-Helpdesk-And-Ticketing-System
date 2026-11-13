# Paper Fixes

Inconsistencies found in `project-paper.md` while turning it into SQL. The schema follows the **data dictionary + ERD**. These notes are only about wording and numbering in the paper.

## 1. BR numbering
- The text says "BR1 to BR15 live in the database", but **there is no BR15**. BR14 is enforced by a reporting query.
  → Either renumber (DB rules = BR1–BR14, app rules = BR15–BR18) or add a BR15 (e.g. "Each Technician qualification is recorded once per category" → composite PK on technician_skills).
- Data dictionary references are off by one in several places:

| Column | Paper says | Should be |
|---|---|---|
| `technician_skills.user_id` / `category_id` | BR5 | none exists. Add a skills rule (see above) |
| `priorities.resolution_hours` | BR10 | BR9 |
| `tickets.status` | BR7 | BR6 |
| `tickets.due_at` | BR11 | BR10 |

## 2. Missing ERD
- The "Entity Relationship Diagram" heading is empty in the MD export. Use `docs/erd.png`.

## 3. Wording
- Background: "tracked the same loosely way" → "tracked just as loosely".
- Purpose says "Building the database is not required yet". Update it now that the system is being built.
- BR11 writes "met"; the view returns "Met". Keep the capitalization consistent.

## 4. Technical notes worth adding to the paper
- `INTERSECT` needs MySQL **8.0.31+** or MariaDB **10.3+**. XAMPP ships MariaDB, which supports it.
- `CHECK` constraints are enforced only on MySQL 8.0.16+ / MariaDB 10.2+.
- Priority hours used in the seed: Low 72, Medium 48, High 24, Urgent 4. Confirm as a team.

