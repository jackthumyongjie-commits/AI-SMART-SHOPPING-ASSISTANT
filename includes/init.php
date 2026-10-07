<?php
/**
 * AI Smart Shopping Assistant
 * Master Bootstrap & Initialization
 */

if (!defined('APP_RUNNING')) {
    define('APP_RUNNING', true);
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/catalog.php';
require_once __DIR__ . '/images.php';
require_once __DIR__ . '/ai.php';

// Start session
start_app_session();
