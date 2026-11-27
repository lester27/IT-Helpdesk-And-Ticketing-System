<?php
/**
 * equipment/index.php — equipment list
 * OWNER: Miguel
 *
 * Access: Admin (full), Technician (read-only)
 *
 * Columns: type, brand, model, serial_number, status badge, warranty_expiry
 *         (highlight if expired / expiring in 30 days), current holder (LEFT JOIN users),
 *         ticket count last 90 days
 * Filters: status, type, "under warranty only"
 * Admin: "Add equipment" button -> form.php ; row "Edit" -> form.php?id=N
 */
require_once __DIR__ . '/../includes/auth.php';
require_role(['Admin', 'Technician']);

$pdo = db();

// Statuses come from the schema's CHECK list; types come from the data
$statuses = EQUIPMENT_STATUSES;
$types    = $pdo->query('SELECT DISTINCT equipment_type FROM equipment ORDER BY equipment_type')->fetchAll(PDO::FETCH_COLUMN);

$fStatus = $_GET['status'] ?? '';
$fType   = $_GET['type'] ?? '';
$fWarr   = !empty($_GET['under_warranty']);

// Only accept filter values that really exist (whitelist), then bind them
if (!in_array($fStatus, $statuses, true)) $fStatus = '';
if (!in_array($fType, $types, true))      $fType = '';

$where  = [];
$params = [];
if ($fStatus !== '') { $where[] = 'e.status = ?'; $params[] = $fStatus; }
if ($fType !== '')   { $where[] = 'e.equipment_type = ?';   $params[] = $fType; }
if ($fWarr)          { $where[] = 'e.warranty_expiry >= CURDATE()'; }

$sql = 'SELECT e.*, u.full_name AS holder_name,
               (SELECT COUNT(*) FROM tickets t
                 WHERE t.equipment_id = e.equipment_id
                   AND t.created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)) AS ticket_count
          FROM equipment e
          LEFT JOIN users u ON u.user_id = e.assigned_user_id'
     . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
     . ' ORDER BY e.equipment_type, e.brand, e.model';
$st = $pdo->prepare($sql);
$st->execute($params);
$equipment = $st->fetchAll(PDO::FETCH_ASSOC);

function equipment_badge(string $status): string
{
    return match ($status) {
        'In Stock'     => 'text-bg-success',
        'Assigned'     => 'text-bg-primary',
        'Under Repair' => 'text-bg-warning',
        'Retired'      => 'text-bg-secondary',
        default        => 'text-bg-light',
    };
}

$today = new DateTimeImmutable('today');
$soon  = $today->modify('+30 days');

$pageTitle = 'Equipment';
require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Equipment</h1>
    <?php if (has_role('Admin')): ?>
        <a class="btn btn-primary" href="form.php"><i class="bi bi-plus-lg"></i> Add Equipment</a>
    <?php endif; ?>
</div>

<!-- ===== FILTER BAR ===== -->
<form method="get" class="card card-body mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label" for="status">Status</label>
            <select class="form-select" id="status" name="status">
                <option value="">All</option>
                <?php foreach ($statuses as $s): ?>
                    <option value="<?= e($s) ?>" <?= $s === $fStatus ? 'selected' : '' ?>><?= e($s) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="type">Type</label>
            <select class="form-select" id="type" name="type">
                <option value="">All</option>
                <?php foreach ($types as $t): ?>
                    <option value="<?= e($t) ?>" <?= $t === $fType ? 'selected' : '' ?>><?= e($t) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" id="under_warranty" name="under_warranty" value="1" <?= $fWarr ? 'checked' : '' ?>>
                <label class="form-check-label" for="under_warranty">Under warranty only</label>
            </div>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button class="btn btn-primary" type="submit">Filter</button>
            <a class="btn btn-outline-secondary" href="index.php">Reset</a>
        </div>
    </div>
</form>

<!-- ===== EQUIPMENT TABLE (row link -> view.php?id=N) ===== -->
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Type</th><th>Brand</th><th>Model</th><th>Serial #</th>
                    <th>Status</th><th>Warranty</th><th>Holder</th>
                    <th class="text-center">Tickets (90d)</th>
                    <?php if (has_role('Admin')): ?><th></th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php if (!$equipment): ?>
                <tr><td colspan="9" class="text-center text-muted py-4">No equipment found.</td></tr>
            <?php endif; ?>
            <?php foreach ($equipment as $row):
                $id = (int)$row['equipment_id'];
                $exp = $row['warranty_expiry'] ? new DateTimeImmutable($row['warranty_expiry']) : null;
                $expired  = $exp && $exp < $today;
                $expiring = $exp && !$expired && $exp <= $soon;
            ?>
                <tr class="<?= $expired ? 'table-danger' : ($expiring ? 'table-warning' : '') ?>">
                    <td><?= e($row['equipment_type']) ?></td>
                    <td><?= e($row['brand']) ?></td>
                    <td><a href="view.php?id=<?= $id ?>"><?= e($row['model']) ?></a></td>
                    <td><code><?= e($row['serial_number']) ?></code></td>
                    <td><span class="badge <?= equipment_badge((string)$row['status']) ?>"><?= e($row['status']) ?></span></td>
                    <td>
                        <?php if ($exp): ?>
                            <?= e($exp->format('Y-m-d')) ?>
                            <?php if ($expired): ?><span class="badge text-bg-danger">Expired</span>
                            <?php elseif ($expiring): ?><span class="badge text-bg-warning">Expiring soon</span><?php endif; ?>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $row['holder_name'] ? e($row['holder_name']) : '<span class="text-muted">Unassigned</span>' ?></td>
                    <td class="text-center"><?= (int)$row['ticket_count'] ?></td>
                    <?php if (has_role('Admin')): ?>
                        <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="form.php?id=<?= $id ?>">Edit</a></td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
