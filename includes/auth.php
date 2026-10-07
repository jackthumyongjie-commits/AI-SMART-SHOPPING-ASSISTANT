<?php
/**
 * AI Smart Shopping Assistant
 * Authentication & CSRF Security Helper
 */

if (!defined('APP_RUNNING')) {
    exit('Direct access denied.');
}

/**
 * Initialize PHP Session securely
 */
function start_app_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        // Set secure cookie params where applicable
        $is_https = function_exists('request_is_https') ? request_is_https() : false;
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $is_https,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        session_start();
    }
}

/**
 * Get current logged in user array or null
 */
function current_user(): ?array {
    start_app_session();
    return $_SESSION['user'] ?? null;
}

/**
 * Check if current user is admin
 */
function is_admin(): bool {
    $user = current_user();
    return $user !== null && ($user['role'] ?? '') === 'admin';
}

/**
 * Require admin access, else redirect to login
 */
function require_admin(): void {
    if (!is_admin()) {
        $_SESSION['flash_error'] = 'Access denied. Please login as administrator.';
        header('Location: ' . APP_URL . '/login.php');
        exit;
    }
}

/**
 * Attempt login with email and password
 */
function attempt_login(string $email, string $password): bool {
    start_app_session();
    
    $user = db_fetch_one("SELECT * FROM users WHERE email = ?", [trim($email)]);
    if ($user && password_verify($password, $user['password'])) {
        // Prevent session fixation. Skip if output already started so login still succeeds.
        if (!headers_sent()) {
            session_regenerate_id(true);
        }
        $_SESSION['user'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role']
        ];
        return true;
    }
    return false;
}

/**
 * Logout current user
 */
function logout_user(): void {
    start_app_session();
    unset($_SESSION['user']);

    // Rotate the session id and keep the new session so a logout flash can be shown.
    if (!headers_sent()) {
        session_regenerate_id(true);
    }
}

/**
 * Generate or get existing CSRF Token
 */
function csrf_token(): string {
    start_app_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF Token from POST request
 */
function csrf_verify(?string $token = null): bool {
    start_app_session();
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    }
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Helper to generate CSRF hidden input field
 */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}
