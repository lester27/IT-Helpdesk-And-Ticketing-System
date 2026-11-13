<?php
/**
 * includes/auth.php — sessions, login state, role checks
 * OWNER: Harold (see docs/TASKS.md)
 *
 * Every protected page starts with:
 *     require_once __DIR__ . '/../includes/auth.php';
 *     require_login();                       // any logged-in user
 *     require_role(['Admin']);               // or restrict by role
 *
 * Session keys set on login: user_id, full_name, role
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** Returns the logged-in user array (user_id, full_name, role) or null. */
function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function has_role(string ...$roles): bool
{
    $u = current_user();
    return $u !== null && in_array($u['role'], $roles, true);
}

/** Redirect to login if not logged in. */
function require_login(): void
{
    if (!is_logged_in()) {
        redirect(BASE_URL . '/login.php');
    }
}

/** 403 if the logged-in user's role is not in $roles. */
function require_role(array $roles): void
{
    require_login();
    if (!has_role(...$roles)) {
        http_response_code(403);
        require __DIR__ . '/../errors/403.php';
        exit;
    }
}

/**
 * Attempt login. Returns true on success.
 * Implemented early so every module can log in and test from day 1.
 * Inactive users cannot log in (BR4).
 */
function attempt_login(string $username, string $password): bool
{
    $stmt = db()->prepare(
        'SELECT user_id, full_name, role, password_hash, is_active
         FROM users WHERE username = ?'
    );
    $stmt->execute([$username]);
    $row = $stmt->fetch();

    if (!$row || !$row['is_active'] || !password_verify($password, $row['password_hash'])) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['user'] = [
        'user_id'   => (int)$row['user_id'],
        'full_name' => $row['full_name'],
        'role'      => $row['role'],
    ];
    return true;
}

function logout(): void
{
    $_SESSION = [];
    session_destroy();
}
