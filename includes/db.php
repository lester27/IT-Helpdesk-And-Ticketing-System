<?php
/**
 * includes/db.php — shared PDO connection
 * OWNER: Myrson (see docs/TASKS.md)
 *
 * Usage in any page:
 *     require_once __DIR__ . '/../includes/db.php';
 *     $stmt = db()->prepare('SELECT * FROM tickets WHERE ticket_id = ?');
 *     $stmt->execute([$id]);
 *     $ticket = $stmt->fetch();
 *
 * RULE: always use prepared statements (? placeholders). Never concatenate
 * user input into SQL.
 */

function db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $configFile = __DIR__ . '/../config/db.php';
    if (!file_exists($configFile)) {
        die('Missing config/db.php — copy config/db.example.php to config/db.php first.');
    }
    $cfg = require $configFile;

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $cfg['host'], $cfg['port'], $cfg['database'], $cfg['charset']
    );

    $pdo = new PDO($dsn, $cfg['username'], $cfg['password'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    return $pdo;
}

