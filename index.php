<?php
/**
 * index.php — entry point
 * Sends logged-in users to the dashboard, everyone else to login.
 */
require_once __DIR__ . '/includes/auth.php';

redirect(BASE_URL . (is_logged_in() ? '/dashboard.php' : '/login.php'));

