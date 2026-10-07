<?php
/**
 * AI Smart Shopping Assistant
 * Core Configuration
 */

// Prevent direct file access
if (!defined('APP_RUNNING')) {
    define('APP_RUNNING', true);
}

// Application Info
define('APP_NAME', 'AI Smart Shopping Assistant');
define('APP_VERSION', '1.0.0');

// cPanel credentials live in includes/config.cpanel.php.
// They are used only on the live domain. Localhost always uses XAMPP.
$cpanelConfig = __DIR__ . '/config.cpanel.php';
if (is_file($cpanelConfig)) {
    require $cpanelConfig;
}

/**
 * This computer is localhost. The uploaded site is a real domain.
 */
function is_local_app(): bool {
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $host = strtolower((string)preg_replace('/:\d+$/', '', $host));
    return in_array($host, ['localhost', '127.0.0.1', '::1'], true);
}

/**
 * Auto-detect active MySQL/MariaDB Port (3306 vs 3307 for XAMPP environments)
 */
function get_db_port(): string {
    static $detected_port = null;
    if ($detected_port !== null) {
        return $detected_port;
    }

    // Try standard 3306 first
    $fp = @fsockopen('127.0.0.1', 3306, $errno, $errstr, 0.5);
    if ($fp) {
        fclose($fp);
        // Test if root can authenticate on 3306
        try {
            $test_pdo = new PDO("mysql:host=127.0.0.1;port=3306", "root", "", [PDO::ATTR_TIMEOUT => 1]);
            $detected_port = '3306';
            return '3306';
        } catch (Exception $e) {
            // Port 3306 occupied by password-protected service, check 3307
        }
    }

    // Try XAMPP custom 3307
    $fp2 = @fsockopen('127.0.0.1', 3307, $errno, $errstr, 0.5);
    if ($fp2) {
        fclose($fp2);
        $detected_port = '3307';
        return '3307';
    }

    $detected_port = '3306';
    return '3306';
}

$useCpanel = defined('CPANEL_DB_NAME') && CPANEL_DB_NAME !== '' && !is_local_app();
define('DB_CPANEL', $useCpanel);

if (DB_CPANEL) {
    define('DB_HOST', CPANEL_DB_HOST);
    define('DB_PORT', '3306');
    define('DB_NAME', CPANEL_DB_NAME);
    define('DB_USER', CPANEL_DB_USER);
    define('DB_PASS', CPANEL_DB_PASS);
} else {
    define('DB_HOST', '127.0.0.1');
    define('DB_PORT', get_db_port());
    define('DB_NAME', 'ai_smart_shopping');
    define('DB_USER', 'root');
    define('DB_PASS', '');
}

define('DB_CHARSET', 'utf8mb4');

/**
 * PDO DSN. cPanel must use host=localhost with no port so PHP uses the
 * MySQL socket. Forcing 127.0.0.1 or a port makes many cPanel accounts fail.
 */
function db_dsn(bool $withDatabase = true): string {
    $dsn = 'mysql:host=' . DB_HOST;
    if (!DB_CPANEL && DB_PORT !== '') {
        $dsn .= ';port=' . DB_PORT;
    }
    if ($withDatabase) {
        $dsn .= ';dbname=' . DB_NAME;
    }
    return $dsn . ';charset=' . DB_CHARSET;
}

// Directory Paths
define('ROOT_PATH', dirname(__DIR__));
define('UPLOADS_DIR', ROOT_PATH . '/uploads');
define('PRODUCT_UPLOADS_DIR', UPLOADS_DIR . '/products');
define('CACHE_UPLOADS_DIR', UPLOADS_DIR . '/cache');

// Max upload file size (5MB)
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024);
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/jpg', 'image/png', 'image/webp']);
define('ALLOWED_IMAGE_EXTS', ['jpg', 'jpeg', 'png', 'webp']);

/**
 * cPanel often terminates HTTPS at a proxy, so PHP still sees a normal HTTP request.
 */
function request_is_https(): bool {
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }
    if ((int)($_SERVER['SERVER_PORT'] ?? 80) === 443) {
        return true;
    }
    $forwarded = strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
    if ($forwarded === 'https') {
        return true;
    }
    return strtolower((string)($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '')) === 'on';
}

/**
 * Automatically determine the dynamic Application Base URL
 * Works seamlessly across localhost XAMPP subfolders and cPanel live domains
 */
function get_app_url(): string {
    $protocol = request_is_https() ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Script name relative folder calculation
    $script_name = $_SERVER['SCRIPT_NAME'] ?? '';
    $dir = dirname($script_name);
    
    // Clean up backslashes for Windows
    $dir = str_replace('\\', '/', $dir);
    
    // Remove /admin or /api from end of dir if calling from subfolder
    $dir = preg_replace('#/(admin|api)$#', '', $dir);
    
    if ($dir === '/' || $dir === '.') {
        $dir = '';
    }
    
    return rtrim("{$protocol}://{$host}{$dir}", '/');
}

define('APP_URL', get_app_url());

if (!function_exists('e')) {
    /**
     * Escape HTML output safely. Available before the full app bootstrap
     * so installer pages can render status messages.
     */
    function e(?string $string): string {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}

// Error Reporting Strategy (Log errors silently, show friendly messages)
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', ROOT_PATH . '/error.log');
