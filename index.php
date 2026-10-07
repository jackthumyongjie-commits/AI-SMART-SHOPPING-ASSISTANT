<?php
/**
 * AI Smart Shopping Assistant
 * Homepage & Customer QR Code Hub
 */

require_once __DIR__ . '/includes/init.php';

// Auto-check installation
if (!is_db_installed()) {
    header('Location: ' . APP_URL . '/install.php');
    exit;
}

$page_title = APP_NAME . ' - Home';
$scan_url = APP_URL . '/scan.php';

require_once __DIR__ . '/includes/header.php';
?>

<div class="hero-section text-center">
    <div class="container position-relative z-1">
        <span class="hero-badge">
            <i class="fas fa-wand-magic-sparkles me-1"></i> AI Computer Vision & Barcode Tech
        </span>
        <h1 class="display-4 fw-extrabold mb-3">AI Smart Shopping Assistant</h1>
        <p class="lead text-light opacity-90 max-w-600 mx-auto mb-4" style="max-width: 650px;">
            Identify retail products using your mobile camera. Scan barcodes or take a photo to check real-time store pricing and detailed product specifications.
        </p>

        <!-- Dynamic QR Code Card -->
        <div class="my-4">
            <div class="qr-wrapper">
                <div id="qrcode-canvas"></div>
                <div class="mt-2 text-dark font-monospace fw-bold small text-break" style="max-width: 250px;">
                    <?= e($scan_url) ?>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <a href="<?= e($scan_url) ?>" class="btn btn-primary-custom btn-lg px-5 py-3 rounded-pill shadow-lg me-2">
                <i class="fas fa-camera me-2"></i> Open Scanner
            </a>
            <a href="<?= APP_URL ?>/products.php" class="btn btn-outline-light btn-lg px-4 py-3 rounded-pill">
                <i class="fas fa-store me-2"></i> View Catalog
            </a>
        </div>
    </div>
</div>

<div class="container my-5">
    <div class="text-center mb-5">
        <h2 class="fw-bold">How It Works</h2>
        <p class="text-secondary">Instant product lookup powered by Barcode & AI Vision</p>
    </div>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card-custom p-4 text-center h-100">
                <div class="bg-primary-subtle text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px;">
                    <i class="fas fa-qrcode fs-3"></i>
                </div>
                <h4 class="fw-bold mb-2">1. Scan QR or Open App</h4>
                <p class="text-secondary small mb-0">
                    No account required for customers. Scan the QR code above with your mobile phone camera to launch the scanner instantly.
                </p>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card-custom p-4 text-center h-100">
                <div class="bg-success-subtle text-success rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px;">
                    <i class="fas fa-barcode fs-3"></i>
                </div>
                <h4 class="fw-bold mb-2">2. Point & Detect</h4>
                <p class="text-secondary small mb-0">
                    Point your camera at any retail product. The built-in ZXing reader automatically detects standard EAN-13, UPC, and Code128 barcodes.
                </p>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card-custom p-4 text-center h-100">
                <div class="bg-purple-subtle text-purple rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px;">
                    <i class="fas fa-brain fs-3 text-info"></i>
                </div>
                <h4 class="fw-bold mb-2">3. AI Visual Fallback</h4>
                <p class="text-secondary small mb-0">
                    No barcode? Tap Capture to send a snapshot to our multi-provider AI model (Agnes / Gemini) which matches the packaging against store inventory.
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Load QRCode JS to render dynamic QR Code -->
<script src="<?= APP_URL ?>/assets/js/vendor/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const qrContainer = document.getElementById('qrcode-canvas');
    if (qrContainer && typeof QRCode !== 'undefined') {
        new QRCode(qrContainer, {
            text: "<?= addslashes($scan_url) ?>",
            width: 200,
            height: 200,
            colorDark: "#1e1b4b",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
