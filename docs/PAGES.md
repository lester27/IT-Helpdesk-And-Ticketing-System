# Pages & Access Matrix

Task owners are listed in [TASKS.md](TASKS.md).

Use this as the testing checklist in Week 4.

| Page | Employee | Technician | Admin | Rules |
|---|:-:|:-:|:-:|---|
| `login.php` | ✓ | ✓ | ✓ | BR4 inactive users can't log in |
| `dashboard.php` | own view | own view | own view | BR12 SLA badges |
| `tickets/index.php` | own tickets | assigned + all | all | |
| `tickets/create.php` | ✓ | – | optional | BR6, BR7, BR11, BR19 |
| `tickets/view.php` | own only | ✓ | ✓ | BR12, BR18 |
| `tickets/update.php` (comment) | own only | ✓ | ✓ | BR9, BR18 |
| `tickets/update.php` (status) | – | if assigned | ✓ | BR9, BR16, BR18 |
| `tickets/assign.php` | – | – | ✓ | BR17, BR18 |
| `equipment/index.php` | – | read | ✓ | |
| `equipment/form.php` | – | – | ✓ | BR13, BR14, BR19 |
| `equipment/view.php` | – | read | ✓ | BR15 |
| `users/index.php` / `form.php` | – | – | ✓ | BR1–BR4 |
| `users/skills.php` | – | – | ✓ | BR5 (composite PK) |
| `departments/index.php` | – | – | ✓ | BR4 (no delete) |
| `reports/index.php` | – | – | ✓ | BR12, BR15, R2–R5 |

## Rule-breaking tests (should all FAIL gracefully)
- [ ] Log in as `tech.old` (inactive)
- [ ] Employee opens another employee's ticket by changing `?id=`
- [ ] Employee opens `/users/index.php` directly → 403
- [ ] Move a ticket from Open straight to Resolved
- [ ] Change a Resolved ticket back to In Progress, or comment on it
- [ ] Assign a Network ticket to `tech.lim` (not qualified)
- [ ] Assign a ticket to `tech.old` (inactive)
- [ ] Give the retired Acer desktop to a user, or link it to a new ticket
- [ ] Create equipment with an existing serial number
- [ ] Create a user with an existing username/email

