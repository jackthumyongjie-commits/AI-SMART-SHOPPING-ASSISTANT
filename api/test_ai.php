<?php
/**
 * AI Smart Shopping Assistant
 * Test AI Connection API Endpoint (Admin Only)
 */

require_once __DIR__ . '/../includes/init.php';

header('Content-Type: application/json; charset=utf-8');

// Require admin authentication
if (!is_admin()) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access.'
    ]);
    exit;
}

$result = test_ai_connection();

echo json_encode($result);
