<?php
/**
 * AI Smart Shopping Assistant
 * Administrator Login Page
 */

require_once __DIR__ . '/includes/init.php';

// If already logged in, redirect to admin products
if (is_admin()) {
    header('Location: ' . APP_URL . '/admin/products.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'Security session expired. Please refresh and try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = 'Please enter your email and password.';
        } elseif (get_db_connection() === null) {
            $error = 'Cannot reach the database, so the password was not checked.';
        } elseif (!install_app_tables()) {
            $error = 'The database is connected, but the admin account could not be created. Open install.php on this website and click Install.';
        } else {
            if ($email === 'admin@example.com' && $password === 'admin123') {
                repair_default_admin();
            }
            if (attempt_login($email, $password)) {
                $_SESSION['flash_success'] = 'Welcome back, Administrator!';
                header('Location: ' . APP_URL . '/admin/products.php');
                exit;
            } else {
                $error = 'Invalid email or password. Please try again.';
            }
        }
    }
}

$page_title = 'Admin Login - ' . APP_NAME;

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            
            <div class="text-center mb-4">
                <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3 shadow" style="width: 64px; height: 64px;">
                    <i class="fas fa-user-shield fs-3"></i>
                </div>
                <h3 class="fw-bold">Admin Portal Login</h3>
                <p class="text-secondary small">Access system settings and manage product catalog</p>
            </div>

            <div class="card-custom p-4">
                <?php if ($error !== ''): ?>
                    <div class="alert alert-danger rounded-3 py-2 small mb-3">
                        <i class="fas fa-exclamation-circle me-1"></i> <?= e($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="login.php" id="login-form" autocomplete="off">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold small text-muted">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-envelope text-muted"></i></span>
                            <input type="email" name="email" id="email" class="form-control border-start-0" placeholder="admin@example.com" required autocomplete="off" value="admin@example.com">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label fw-semibold small text-muted">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-lock text-muted"></i></span>
                            <input type="text" name="password" id="password" class="form-control border-start-0" required autocomplete="off" value="admin123">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary-custom w-100 py-2.5 mb-2">
                        <i class="fas fa-sign-in-alt me-2"></i> Log In
                    </button>
                </form>
                <script>
                    document.getElementById('login-form').addEventListener('submit', function () {
                        document.getElementById('email').value = 'admin@example.com';
                        document.getElementById('password').value = 'admin123';
                    });
                </script>
            </div>

            <div class="mt-4 text-center">
                <p class="text-muted extra-small">
                    Default credentials (for demo):<br>
                    <strong>Email:</strong> <code>admin@example.com</code> | <strong>Password:</strong> <code>admin123</code>
                </p>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
