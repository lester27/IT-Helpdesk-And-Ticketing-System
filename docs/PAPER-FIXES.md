# Paper Fixes

Issues still open in `project-paper.md` after the latest update. The repo (SQL, PHP comments, docs) already uses the **new BR numbering**: BR1–BR15 in the database, BR16–BR19 in the application.

## 1. BR references in the data dictionary
Fixed in `docs/project-paper.md` (equipment rows now cite BR13 and BR14). **Mirror this in the source docx.**

## 2. Missing ERD
- The "Entity Relationship Diagram" heading is empty in the MD export. Insert `docs/erd.png` (in the docx).

## 3. Wording
- Purpose sentence "Building the database is not required yet" was replaced in the MD. **Mirror this in the docx.**
- BR12 says "met" but the view returns "Met". Keep the capitalization consistent.
- The stray empty `####` headings around the BR5 table were removed in the MD. Delete them in the docx too.

## 4. Technical notes worth adding to the paper
- `INTERSECT` needs MySQL **8.0.31+** or MariaDB **10.3+**. XAMPP ships MariaDB, which supports it.
- `CHECK` constraints are enforced only on MySQL 8.0.16+ / MariaDB 10.2+.
- Priority hours used in the seed: Low 72, Medium 48, High 24, Urgent 4. Confirm as a team.
