<?php
/**
 * AI Smart Shopping Assistant
 * Common Header Template
 */

if (!defined('APP_RUNNING')) {
    require_once __DIR__ . '/init.php';
}

$page_title = $page_title ?? 'AI Smart Shopping Assistant';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?></title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Custom Application Styling -->
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/app.css">
</head>
<body>

    <!-- Header Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-custom py-2.5">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="<?= APP_URL ?>/index.php">
                <div class="bg-primary text-white rounded-3 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                    <i class="fas fa-robot"></i>
                </div>
                <span class="brand-title"><?= e(APP_NAME) ?></span>
            </a>

            <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
                <i class="fas fa-bars fs-4 text-dark"></i>
            </button>

            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-2 mt-3 mt-lg-0">
                    <li class="nav-item">
                        <a class="nav-link fw-semibold text-dark px-3" href="<?= APP_URL ?>/index.php">
                            <i class="fas fa-home me-1 text-primary"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-semibold text-dark px-3" href="<?= APP_URL ?>/scan.php">
                            <i class="fas fa-qrcode me-1 text-primary"></i> Scanner
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-semibold text-dark px-3" href="<?= APP_URL ?>/products.php">
                            <i class="fas fa-shopping-bag me-1 text-primary"></i> Catalog
                        </a>
                    </li>

                    <?php if (is_admin()): ?>
                        <li class="nav-item dropdown ms-lg-2">
                            <a class="btn btn-outline-primary btn-sm rounded-pill dropdown-toggle px-3 py-1.5 fw-bold" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-shield me-1"></i> Admin Portal
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-4 mt-2">
                                <li>
                                    <a class="dropdown-item py-2 fw-semibold" href="<?= APP_URL ?>/admin/products.php">
                                        <i class="fas fa-boxes me-2 text-primary"></i> Manage Products
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item py-2 fw-semibold" href="<?= APP_URL ?>/admin/settings.php">
                                        <i class="fas fa-sliders-h me-2 text-primary"></i> AI Settings
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item py-2 text-danger fw-semibold" href="<?= APP_URL ?>/logout.php">
                                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item ms-lg-2">
                            <a class="btn btn-outline-secondary btn-sm rounded-pill px-3 py-1.5 fw-semibold" href="<?= APP_URL ?>/login.php">
                                <i class="fas fa-lock me-1"></i> Admin Login
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Global Flash Alerts -->
    <div class="container mt-3">
        <?php if (!empty($_SESSION['flash_success'])): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm" role="alert">
                <i class="fas fa-check-circle me-2"></i> <?= e($_SESSION['flash_success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['flash_error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i> <?= e($_SESSION['flash_error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>
    </div>
