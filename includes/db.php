<?php
/**
 * AI Smart Shopping Assistant
 * Database PDO Handler
 */

if (!defined('APP_RUNNING')) {
    exit('Direct access denied.');
}

/**
 * Get singleton PDO instance
 */
function get_db_connection(): ?PDO {
    static $pdo = null;

    if ($pdo === null) {
        try {
            $dsn = db_dsn(true);
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            if (defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
                $options[PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES utf8mb4';
            }

            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            return null;
        }
    }

    return $pdo;
}

/**
 * Check if Database and Tables are initialized
 */
function database_has_table(string $table): bool {
    $pdo = get_db_connection();
    if (!$pdo) {
        return false;
    }

    try {
        $stmt = $pdo->query('SHOW TABLES LIKE ' . $pdo->quote($table));
        return $stmt !== false && $stmt->fetchColumn() !== false;
    } catch (Exception $e) {
        return false;
    }
}

function import_sql_file(PDO $pdo, string $path): void {
    $sql = file_get_contents($path);
    if ($sql === false || trim($sql) === '') {
        throw new RuntimeException('SQL file is missing or empty: ' . basename($path));
    }

    $sql = preg_replace('/^\s*--.*$/m', '', $sql);
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($statements as $statement) {
        $pdo->exec($statement);
    }
}

/**
 * Create the catalog tables when the live database is still empty.
 * A missing table used to look like a wrong password on the login page.
 */
function install_app_tables(): bool {
    $pdo = get_db_connection();
    if (!$pdo) {
        return false;
    }
    if (database_has_table('users') && database_has_table('products') && database_has_table('settings')) {
        return true;
    }

    try {
        import_sql_file($pdo, ROOT_PATH . '/sql/schema.sql');
        import_sql_file($pdo, ROOT_PATH . '/sql/seed.sql');
        if (!function_exists('create_sample_product_images')) {
            require_once __DIR__ . '/images.php';
        }
        create_sample_product_images();
        return database_has_table('users');
    } catch (Throwable $e) {
        error_log('Could not create application tables: ' . $e->getMessage());
        return false;
    }
}

function repair_default_admin(): void {
    if (!database_has_table('users')) {
        return;
    }

    $hash = password_hash('admin123', PASSWORD_DEFAULT);
    $user = db_fetch_one('SELECT password, role FROM users WHERE email = ?', ['admin@example.com']);
    if (!$user) {
        db_query(
            'INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)',
            ['Store Administrator', 'admin@example.com', $hash, 'admin']
        );
        return;
    }

    $passwordOk = password_verify('admin123', (string)$user['password']);
    if (!$passwordOk || ($user['role'] ?? '') !== 'admin') {
        db_query(
            'UPDATE users SET password = ?, role = ? WHERE email = ?',
            [$hash, 'admin', 'admin@example.com']
        );
    }
}

function is_db_installed(): bool {
    return database_has_table('products');
}

/**
 * Helper to run prepared queries safely
 */
function db_query(string $sql, array $params = []): ?PDOStatement {
    $pdo = get_db_connection();
    if (!$pdo) {
        return null;
    }

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("SQL Error: " . $e->getMessage() . " | Query: " . $sql);
        return null;
    }
}

/**
 * Fetch a single record
 */
function db_fetch_one(string $sql, array $params = []): ?array {
    $stmt = db_query($sql, $params);
    if ($stmt) {
        $result = $stmt->fetch();
        return $result ?: null;
    }
    return null;
}

/**
 * Fetch all matching records
 */
function db_fetch_all(string $sql, array $params = []): array {
    $stmt = db_query($sql, $params);
    if ($stmt) {
        return $stmt->fetchAll() ?: [];
    }
    return [];
}
