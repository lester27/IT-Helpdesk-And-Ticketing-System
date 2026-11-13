# Contributing

## Git workflow

> **New to branches?** Read the step-by-step **[Git Guide](docs/GIT-GUIDE.md)** first. It covers creating a branch, pushing, opening a PR, updating your branch and fixing merge conflicts.

1. **Never commit directly to `main`.** `main` must always run.
2. Pick your GitHub Issue, then create a branch from the latest `main`:
   ```bash
   git checkout main
   git pull
   git checkout -b feature/tickets-create
   ```
   Branch names: `feature/<area>-<thing>`, `fix/<thing>`, `db/<thing>`, `docs/<thing>`
3. Commit small and often:
   ```
   tickets: add create form validation
   db: add index on tickets.due_at
   ```
4. Push and open a **Pull Request** into `main`. Link the issue (`Closes #12`).
5. At least **one teammate reviews** before merging. Lester (team lead) does the merge.
6. After merging, everyone runs `git pull` on `main`.

### Avoiding conflicts

- Stay inside the folder you own (see [docs/TASKS.md](docs/TASKS.md)).
- **Shared files** (`includes/*`, `database/01_schema.sql`) → tell the group chat before editing, keep the PR small.
- **Schema changes** go through Myrson (Database owner). If you need a column, open an issue instead of editing the SQL yourself.

## Coding conventions

### PHP
- Every page starts with:
  ```php
  require_once __DIR__ . '/../includes/auth.php';
  require_login();              // or require_role(['Admin']);
  ```
- **Prepared statements only** via `db()->prepare(...)`. Never put `$_GET`/`$_POST` directly in SQL.
- Escape all output with `e($value)`.
- Every POST form includes `<?= csrf_field() ?>` and the handler calls `verify_csrf()`.
- After a successful POST: `flash('success', '...')` then `redirect(...)` (Post/Redirect/Get).
- Multi-step writes (e.g. status change + history row) use `db()->beginTransaction()` / `commit()` / `rollBack()`.
- Don't set `due_at` or `created_at` from PHP. The database does that.

### HTML / CSS
- Bootstrap 5 classes only. Use `container`, `row`, `col-*`, `card`, `table`, `badge`, `btn`, `form-control`, `form-select`.
- Use `status_badge()` and `sla_badge()` from `helpers.php` so colors match on every page.

### SQL
- snake_case names, matching the data dictionary exactly.
- Report queries live in `database/03_reports.sql`. If you change one, update the page that uses it too.

## Definition of done

- [ ] Works with every role it applies to (log in as each demo account)
- [ ] Enforces the business rules listed in its file header
- [ ] No PHP warnings/notices on screen
- [ ] PR reviewed and merged

