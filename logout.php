<?php
/**
 * AI Smart Shopping Assistant
 * Logout Script
 */

require_once __DIR__ . '/includes/init.php';

logout_user();

start_app_session();
$_SESSION['flash_success'] = 'You have been logged out successfully.';
header('Location: ' . APP_URL . '/index.php');
exit;
