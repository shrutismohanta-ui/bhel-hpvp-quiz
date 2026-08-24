<?php
/**
 * BHEL-HPVP Vizag Quiz Application
 * Logout Handler
 */

require_once __DIR__ . '/includes/auth.php';

logout_user();
header('Location: index.php?message=' . urlencode('You have been logged out successfully.'));
exit();
