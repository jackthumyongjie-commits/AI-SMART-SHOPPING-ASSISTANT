<?php
/**
 * AI Smart Shopping Assistant
 * Settings Key-Value Manager
 */

if (!defined('APP_RUNNING')) {
    exit('Direct access denied.');
}

/**
 * Retrieve a setting value by key
 */
function get_setting(string $key, string $default = ''): string {
    $row = db_fetch_one("SELECT setting_value FROM settings WHERE setting_key = ?", [$key]);
    if ($row !== null && isset($row['setting_value'])) {
        return (string)$row['setting_value'];
    }
    return $default;
}

/**
 * Save or update a setting
 */
function save_setting(string $key, string $value): bool {
    $stmt = db_query(
        "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)",
        [$key, $value]
    );
    return $stmt !== null;
}

/**
 * Get all settings as an associative key-value array
 */
function get_all_settings(): array {
    $rows = db_fetch_all("SELECT setting_key, setting_value FROM settings");
    $settings = [];
    foreach ($rows as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    return $settings;
}

/**
 * Mask API key for secure Admin display (e.g., ****************abcd)
 */
function mask_api_key(string $key): string {
    $length = strlen($key);
    if ($length === 0) {
        return '';
    }
    if ($length <= 4) {
        return str_repeat('*', $length);
    }
    $visible = substr($key, -4);
    return str_repeat('*', min(16, $length - 4)) . $visible;
}
