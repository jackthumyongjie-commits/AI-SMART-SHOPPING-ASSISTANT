<?php
/**
 * AI Smart Shopping Assistant
 * 1-Click Installation & Database Initialization System
 */

define('APP_RUNNING', true);
require_once __DIR__ . '/includes/config.php';

/**
 * Run a .sql file one statement at a time.
 * PDO multi-statements are often disabled, so a single exec() of the whole file fails.
 */
function execute_sql_file(PDO $pdo, string $path): void {
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

$message = '';
$status_items = [];
$installed = false;

function ensure_writable_dir(string $dir): void {
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new RuntimeException('Could not create folder: ' . $dir);
    }
    if (!is_writable($dir)) {
        throw new RuntimeException('Folder is not writable: ' . $dir . '. In cPanel File Manager, set this folder to 755.');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['auto'])) {
    try {
        if (PHP_VERSION_ID < 80000) {
            throw new RuntimeException('PHP 8.0 or newer is required. In cPanel open Select PHP Version and choose 8.1 or 8.2.');
        }
        $missing = [];
        foreach (['pdo_mysql', 'curl', 'fileinfo', 'json'] as $extension) {
            if (!extension_loaded($extension)) {
                $missing[] = $extension;
            }
        }
        if ($missing !== []) {
            throw new RuntimeException('PHP is missing: ' . implode(', ', $missing) . '. Enable them in cPanel → Select PHP Version → Extensions.');
        }

        ensure_writable_dir(UPLOADS_DIR);
        ensure_writable_dir(PRODUCT_UPLOADS_DIR);
        ensure_writable_dir(CACHE_UPLOADS_DIR);

        $pdoOptions = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
        if (defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
            $pdoOptions[PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES utf8mb4';
        }

        if (DB_CPANEL) {
            if (DB_NAME === '' || DB_USER === '' || DB_NAME === 'cpaneluser_ai_smart_shopping' || !defined('CPANEL_DB_NAME')) {
                throw new RuntimeException('Fill DB_NAME, DB_USER, and DB_PASS in includes/config.cpanel.php with the database you created in cPanel.');
            }
            // cPanel users cannot CREATE DATABASE. The database must already exist.
            $pdo = new PDO(db_dsn(true), DB_USER, DB_PASS, $pdoOptions);
            $status_items[] = ['name' => 'Connected to existing cPanel database (' . DB_NAME . ')', 'status' => true];
        } else {
            $server = new PDO(db_dsn(false), DB_USER, DB_PASS, $pdoOptions);
            $server->exec("CREATE DATABASE IF NOT EXISTS `" . str_replace('`', '``', DB_NAME) . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $status_items[] = ['name' => 'Database Creation (' . DB_NAME . ')', 'status' => true];
            $pdo = new PDO(db_dsn(true), DB_USER, DB_PASS, $pdoOptions);
        }

        // Step 4: Import Schema
        execute_sql_file($pdo, __DIR__ . '/sql/schema.sql');
        $status_items[] = ['name' => 'Database Tables Schema', 'status' => true];

        // Step 5: Import Seed Data
        execute_sql_file($pdo, __DIR__ . '/sql/seed.sql');
        $status_items[] = ['name' => 'Seed Data & Default Settings', 'status' => true];

        // Step 6: Generate Sample Images
        require_once __DIR__ . '/includes/images.php';
        create_sample_product_images();
        $status_items[] = ['name' => 'Sample Product Images', 'status' => true];

        $installed = true;
    } catch (Exception $e) {
        $detail = $e->getMessage();
        if (DB_PASS !== '') {
            $detail = str_replace(DB_PASS, '***', $detail);
        }
        $status_items[] = ['name' => 'Installation Error: ' . $detail, 'status' => false];
    }
} else {
    // Check if already installed
    require_once __DIR__ . '/includes/db.php';
    if (is_db_installed()) {
        $installed = true;
        $status_items[] = ['name' => 'Database and catalog tables are already initialized.', 'status' => true];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Installer - AI Smart Shopping Assistant</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/app.css">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-6">
                
                <div class="text-center mb-4">
                    <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3 shadow" style="width: 72px; height: 72px;">
                        <i class="fas fa-magic fs-2"></i>
                    </div>
                    <h2 class="fw-bold">System Setup & Installation</h2>
                    <p class="text-secondary small">AI Smart Shopping Assistant Initialization Wizard</p>
                </div>

                <div class="card-custom p-4">
                    <h5 class="fw-bold mb-3 border-bottom pb-2">Database & Environment Status</h5>

                    <ul class="list-group list-group-flush mb-4">
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span><i class="fab fa-php text-primary me-2"></i> PHP Version</span>
                            <span class="badge bg-success font-monospace"><?= PHP_VERSION ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span><i class="fas fa-server text-secondary me-2"></i> Database Mode</span>
                            <span class="badge <?= DB_CPANEL ? 'bg-primary' : 'bg-secondary' ?>"><?= DB_CPANEL ? 'cPanel' : 'Local XAMPP' ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span><i class="fas fa-database text-info me-2"></i> MySQL Target Host</span>
                            <span class="font-monospace text-muted"><?= e(DB_HOST) ?><?= DB_CPANEL ? '' : ':' . e(DB_PORT) ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span><i class="fas fa-folder text-warning me-2"></i> Target Database</span>
                            <span class="font-monospace text-dark fw-bold"><?= e(DB_NAME) ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span><i class="fas fa-user text-secondary me-2"></i> Database User</span>
                            <span class="font-monospace text-dark"><?= e(DB_USER) ?></span>
                        </li>
                    </ul>

                    <?php if (!DB_CPANEL): ?>
                        <div class="alert alert-warning border-0 rounded-4 p-3 mb-4 small">
                            This page is using the local XAMPP database. Upload the project, including <code>includes/config.cpanel.php</code>, then open <strong>install.php on your live domain</strong>. The badge above must say <strong>cPanel</strong> before you install.
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($status_items)): ?>
                        <div class="mb-4">
                            <h6 class="fw-bold text-dark mb-2">Execution Logs:</h6>
                            <?php foreach ($status_items as $item): ?>
                                <div class="alert <?= $item['status'] ? 'alert-success' : 'alert-danger' ?> py-2 px-3 small rounded-3 mb-2 d-flex align-items-center">
                                    <i class="fas <?= $item['status'] ? 'fa-check-circle text-success' : 'fa-times-circle text-danger' ?> me-2"></i>
                                    <?= e($item['name']) ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($installed): ?>
                        <div class="alert alert-info border-0 rounded-4 p-3 mb-4">
                            <h6 class="fw-bold mb-1"><i class="fas fa-info-circle me-1"></i> Default Administrator Credentials</h6>
                            <p class="small mb-2">
                                <strong>Email:</strong> <code>admin@example.com</code><br>
                                <strong>Password:</strong> <code>admin123</code>
                            </p>
                            <p class="small mb-0">After the homepage opens, delete <code>install.php</code> from the server. API keys are not copied to the live database, so enter them again under AI Settings.</p>
                        </div>

                        <div class="d-grid gap-2">
                            <a href="<?= APP_URL ?>/index.php" class="btn btn-primary-custom py-2.5">
                                <i class="fas fa-home me-2"></i> Open Homepage & Scanner QR
                            </a>
                            <a href="<?= APP_URL ?>/login.php" class="btn btn-outline-secondary py-2">
                                <i class="fas fa-lock me-2"></i> Admin Login
                            </a>
                        </div>
                    <?php else: ?>
                        <form method="POST" action="install.php">
                            <button type="submit" class="btn btn-primary-custom w-100 py-3">
                                <i class="fas fa-play me-2"></i> Install Database & Seed Data Now
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>
</body>
</html>
