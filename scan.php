<?php
/**
 * AI Smart Shopping Assistant
 * Mobile Camera Scanner Page
 */

require_once __DIR__ . '/includes/init.php';

// Auto-check installation
if (!is_db_installed()) {
    header('Location: ' . APP_URL . '/install.php');
    exit;
}

$page_title = 'Product Scanner - ' . APP_NAME;

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8">
            
            <div class="text-center mb-3">
                <h2 class="fw-bold mb-1">
                    <i class="fas fa-camera text-primary me-2"></i> Smart Product Scanner
                </h2>
                <p class="text-secondary small">Point camera at barcode or tap Capture for AI recognition</p>
            </div>

            <!-- Live Camera View Container -->
            <div id="scanner-container" class="scanner-container">
                <!-- Video Stream -->
                <video id="camera-video" playsinline autoplay muted></video>

                <!-- Status Pill Top Bar -->
                <div id="scan-status-pill" class="status-pill">
                    <span id="scan-status-dot" class="status-dot"></span>
                    <span id="scan-status-text">Starting camera...</span>
                </div>

                <!-- Overlay Viewfinder & Laser Scan Line -->
                <div class="scanner-overlay">
                    <div class="scan-viewfinder">
                        <div class="scan-laser"></div>
                    </div>
                </div>

                <!-- Scanner Controls Bottom Bar -->
                <div class="scanner-controls">
                    <button type="button" id="btn-switch-camera" class="btn-camera-ctrl" title="Switch Camera">
                        <i class="fas fa-sync-alt me-1"></i> Flip
                    </button>

                    <button type="button" id="btn-capture" class="btn-capture" title="Capture for AI">
                        <i class="fas fa-camera"></i>
                    </button>

                    <button type="button" id="btn-retake" class="btn-camera-ctrl" title="Reset View">
                        <i class="fas fa-redo me-1"></i> Reset
                    </button>
                </div>
            </div>

            <!-- Manual lookup works even when the camera is blocked -->
            <div class="card-custom p-3 mt-3">
                <form id="manual-barcode-form" class="d-flex gap-2" autocomplete="off">
                    <input type="text" id="manual-barcode" class="form-control" placeholder="Barcode, SKU, or product name" aria-label="Barcode, SKU, or product name">
                    <button type="submit" class="btn btn-primary-custom flex-shrink-0">Lookup</button>
                </form>
                <div class="d-flex flex-wrap gap-2 mt-3">
                    <button type="button" class="btn btn-sm btn-outline-secondary sample-barcode" data-code="8857200535366">Hong Thai</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary sample-barcode" data-code="8850123456789">Nescafé</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary sample-barcode" data-code="8851234567890">Red Bull</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary sample-barcode" data-code="SKU-CHO-004">Cadbury</button>
                </div>
                <div class="mt-3">
                    <label for="ai-file" class="form-label fw-semibold small mb-1">Product photo for AI recognition</label>
                    <input type="file" id="ai-file" class="form-control" accept="image/jpeg,image/png,image/webp">
                    <div class="form-text">Use this when the camera is unavailable. AI still needs an API key in Admin Settings.</div>
                </div>
            </div>

            <!-- Product Result Card (Dynamically Rendered by scan.js) -->
            <div id="result-container" class="mt-4" style="display: none;"></div>

            <div class="mt-4 text-center">
                <a href="<?= APP_URL ?>/products.php" class="text-decoration-none text-muted small">
                    <i class="fas fa-search me-1"></i> Search Catalog Manually
                </a>
            </div>

        </div>
    </div>
</div>

<!-- Load ZXing Barcode Library & Custom Scan JS -->
<script>window.APP_BASE = <?= json_encode(APP_URL, JSON_UNESCAPED_SLASHES) ?>;</script>
<script src="<?= APP_URL ?>/assets/js/vendor/zxing.min.js"></script>
<script src="<?= APP_URL ?>/assets/js/scan.js"></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
