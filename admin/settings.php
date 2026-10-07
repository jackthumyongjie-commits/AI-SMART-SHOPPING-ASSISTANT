<?php
/**
 * AI Smart Shopping Assistant
 * Admin AI Settings & Configuration Page
 */

require_once __DIR__ . '/../includes/init.php';

// Enforce Admin Access
require_admin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'CSRF validation failed. Please try again.';
    } else {
        $provider = trim($_POST['ai_provider'] ?? 'agnes');
        
        save_setting('ai_provider', $provider);

        // Agnes AI / OpenAI settings
        save_setting('agnes_api_url', trim($_POST['agnes_api_url'] ?? 'https://apihub.agnes-ai.com/v1'));
        if (!empty($_POST['agnes_api_key'])) {
            save_setting('agnes_api_key', trim($_POST['agnes_api_key']));
        }
        save_setting('agnes_model', trim($_POST['agnes_model'] ?? 'agnes-2.5-flash'));
        save_setting('agnes_fallback_models', trim($_POST['agnes_fallback_models'] ?? ''));

        // Gemini settings
        save_setting('gemini_api_url', trim($_POST['gemini_api_url'] ?? 'https://generativelanguage.googleapis.com/v1beta'));
        if (!empty($_POST['gemini_api_key'])) {
            save_setting('gemini_api_key', trim($_POST['gemini_api_key']));
        }
        save_setting('gemini_model', trim($_POST['gemini_model'] ?? 'gemini-3.5-flash-lite'));
        save_setting('gemini_fallback_models', trim($_POST['gemini_fallback_models'] ?? ''));

        $_SESSION['flash_success'] = 'AI settings updated successfully.';
        header('Location: ' . APP_URL . '/admin/settings.php');
        exit;
    }
}

$ai_provider = get_setting('ai_provider', 'agnes');
$agnes_url   = get_setting('agnes_api_url', 'https://apihub.agnes-ai.com/v1');
$agnes_key   = get_setting('agnes_api_key', '');
$agnes_model = get_setting('agnes_model', 'agnes-2.5-flash');
$agnes_fallbacks = get_setting('agnes_fallback_models', 'agnes-2.0-flash');

$gemini_url   = get_setting('gemini_api_url', 'https://generativelanguage.googleapis.com/v1beta');
$gemini_key   = get_setting('gemini_api_key', '');
$gemini_model = get_setting('gemini_model', 'gemini-3.5-flash-lite');
$gemini_fallbacks = get_setting('gemini_fallback_models', 'gemini-2.5-flash-lite, gemini-flash-lite-latest');

$page_title = 'AI Settings - ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-1">
                        <i class="fas fa-sliders-h text-primary me-2"></i> AI Configuration Settings
                    </h2>
                    <p class="text-secondary small mb-0">Manage AI computer vision endpoints, API keys, and models</p>
                </div>

                <a href="<?= APP_URL ?>/admin/products.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Products
                </a>
            </div>

            <div class="card-custom p-4 mb-4">
                <form method="POST" action="settings.php">
                    <?= csrf_field() ?>

                    <!-- Select AI Provider -->
                    <div class="mb-4 pb-3 border-bottom">
                        <label class="form-label fw-bold">Active AI Vision Provider</label>
                        <div class="d-flex gap-4">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="ai_provider" id="provider_agnes" value="agnes" <?= $ai_provider === 'agnes' ? 'checked' : '' ?> onchange="toggleProviderTabs()">
                                <label class="form-check-label fw-semibold" for="provider_agnes">
                                    <i class="fas fa-robot text-primary me-1"></i> Agnes AI / OpenAI-compatible
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="ai_provider" id="provider_gemini" value="gemini" <?= $ai_provider === 'gemini' ? 'checked' : '' ?> onchange="toggleProviderTabs()">
                                <label class="form-check-label fw-semibold" for="provider_gemini">
                                    <i class="fab fa-google text-success me-1"></i> Google Gemini
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Agnes AI / OpenAI Tab -->
                    <div id="section_agnes" class="provider-section mb-4" style="<?= $ai_provider === 'agnes' ? '' : 'display:none;' ?>">
                        <h5 class="fw-bold text-dark mb-3">Agnes AI / OpenAI Configuration</h5>
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">API Base URL</label>
                            <input type="url" name="agnes_api_url" class="form-control" value="<?= e($agnes_url) ?>" placeholder="https://apihub.agnes-ai.com/v1">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small">API Key</label>
                            <input type="password" name="agnes_api_key" class="form-control" placeholder="<?= !empty($agnes_key) ? mask_api_key($agnes_key) : 'Enter API Secret Key' ?>">
                            <div class="form-text">Key is masked for security. Leave empty to keep existing key.</div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Primary Model</label>
                                <input type="text" name="agnes_model" class="form-control" value="<?= e($agnes_model) ?>" placeholder="agnes-2.5-flash">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Fallback Models (Comma Separated)</label>
                                <input type="text" name="agnes_fallback_models" class="form-control" value="<?= e($agnes_fallbacks) ?>" placeholder="agnes-2.0-flash">
                            </div>
                        </div>
                    </div>

                    <!-- Google Gemini Tab -->
                    <div id="section_gemini" class="provider-section mb-4" style="<?= $ai_provider === 'gemini' ? '' : 'display:none;' ?>">
                        <h5 class="fw-bold text-dark mb-3">Google Gemini Configuration</h5>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small">API Base URL</label>
                            <input type="url" name="gemini_api_url" class="form-control" value="<?= e($gemini_url) ?>" placeholder="https://generativelanguage.googleapis.com/v1beta">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small">API Key</label>
                            <input type="password" name="gemini_api_key" class="form-control" placeholder="<?= !empty($gemini_key) ? mask_api_key($gemini_key) : 'Enter Gemini API Key' ?>">
                            <div class="form-text">Key is masked for security. Leave empty to keep existing key.</div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Primary Model</label>
                                <input type="text" name="gemini_model" class="form-control" value="<?= e($gemini_model) ?>" placeholder="gemini-3.5-flash-lite">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Fallback Models (Comma Separated)</label>
                                <input type="text" name="gemini_fallback_models" class="form-control" value="<?= e($gemini_fallbacks) ?>" placeholder="gemini-2.5-flash-lite, gemini-flash-lite-latest">
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                        <button type="button" id="btn-test-ai" class="btn btn-outline-primary fw-bold rounded-pill px-4">
                            <i class="fas fa-plug me-2"></i> Test AI Connection
                        </button>

                        <button type="submit" class="btn btn-primary-custom px-5">
                            <i class="fas fa-save me-2"></i> Save Settings
                        </button>
                    </div>
                </form>
            </div>

            <!-- Test Connection Response Card -->
            <div id="test-result-card" class="card-custom p-4" style="display: none;">
                <h5 class="fw-bold mb-2" id="test-result-title">Connection Status</h5>
                <div id="test-result-body"></div>
            </div>

        </div>
    </div>
</div>

<script>
function toggleProviderTabs() {
    var isAgnes = document.getElementById('provider_agnes').checked;
    document.getElementById('section_agnes').style.display = isAgnes ? 'block' : 'none';
    document.getElementById('section_gemini').style.display = isAgnes ? 'none' : 'block';
}

document.getElementById('btn-test-ai')?.addEventListener('click', async function() {
    var resultCard = document.getElementById('test-result-card');
    var resultTitle = document.getElementById('test-result-title');
    var resultBody = document.getElementById('test-result-body');

    resultCard.style.display = 'block';
    resultTitle.innerHTML = '<i class="fas fa-spinner fa-spin text-primary me-2"></i> Testing AI Connection...';
    resultBody.innerHTML = '<p class="text-secondary small mb-0">Sending test ping to configured API endpoint...</p>';

    try {
        var response = await fetch('<?= APP_URL ?>/api/test_ai.php');
        var data = await response.json();

        if (data.success) {
            resultTitle.innerHTML = '<i class="fas fa-check-circle text-success me-2"></i> Connection Successful';
            resultBody.innerHTML = `
                <div class="alert alert-success border-0 mb-0">
                    <strong>Provider:</strong> ${data.provider}<br>
                    <strong>Model:</strong> ${data.model}<br>
                    <strong>Status:</strong> ${data.message}
                </div>
            `;
        } else {
            resultTitle.innerHTML = '<i class="fas fa-times-circle text-danger me-2"></i> Connection Failed';
            resultBody.innerHTML = `
                <div class="alert alert-danger border-0 mb-0">
                    <p class="mb-1"><strong>Error:</strong> ${data.message}</p>
                    <hr class="my-2">
                    <p class="small mb-0">A 503 high-demand message means the address and key are fine. Wait a moment and test again, or switch to another model.</p>
                </div>
            `;
        }
    } catch (e) {
        resultTitle.innerHTML = '<i class="fas fa-exclamation-triangle text-warning me-2"></i> Request Error';
        resultBody.innerHTML = '<p class="text-danger small mb-0">Unable to reach local test endpoint.</p>';
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
