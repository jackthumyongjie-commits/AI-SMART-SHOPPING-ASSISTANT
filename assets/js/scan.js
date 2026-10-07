/**
 * AI Smart Shopping Assistant
 * Camera Controller, Barcode Scanner (ZXing) & AI Image Recognition Engine
 */

document.addEventListener('DOMContentLoaded', () => {
    // DOM Element References
    const videoElem = document.getElementById('camera-video');
    const statusPill = document.getElementById('scan-status-pill');
    const statusText = document.getElementById('scan-status-text');
    const statusDot = document.getElementById('scan-status-dot');
    const btnCapture = document.getElementById('btn-capture');
    const btnSwitchCamera = document.getElementById('btn-switch-camera');
    const btnRetake = document.getElementById('btn-retake');
    const resultContainer = document.getElementById('result-container');
    const cameraWrapper = document.getElementById('scanner-container');
    const manualForm = document.getElementById('manual-barcode-form');
    const manualInput = document.getElementById('manual-barcode');
    const fileInput = document.getElementById('ai-file');
    const APP_BASE = String(window.APP_BASE || '').replace(/\/$/, '');

    // Camera & Scanner State
    let codeReader = null;
    let currentStream = null;
    let currentFacingMode = 'environment'; // Prefer rear camera on mobile
    let isProcessing = false;
    let selectedVideoInputId = null;
    let availableDevices = [];

    // Helper: Update UI Status Indicator
    function setStatus(message, type = 'ready') {
        if (!statusText) return;
        statusText.textContent = message;

        if (statusDot) {
            statusDot.className = 'status-dot';
            if (type === 'busy') statusDot.classList.add('busy');
            if (type === 'error') statusDot.classList.add('error');
        }
    }

    // Check Camera Support
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        setStatus('Camera API not supported in this browser.', 'error');
        showCameraError('Your browser does not support native camera access. Please use Chrome or Safari over HTTPS.');
        return;
    }

    // Initialize ZXing Reader
    if (typeof ZXing !== 'undefined') {
        try {
            codeReader = new ZXing.BrowserMultiFormatReader();
        } catch (e) {
            console.warn('ZXing initialization warning:', e);
        }
    }

    // Start Camera Stream
    async function startCamera() {
        setStatus('Starting camera...', 'busy');

        try {
            // Stop any active stream first
            stopCamera();

            const constraints = {
                video: {
                    facingMode: { ideal: currentFacingMode },
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                },
                audio: false
            };

            currentStream = await navigator.mediaDevices.getUserMedia(constraints);
            videoElem.srcObject = currentStream;
            await videoElem.play();

            setStatus('Camera ready. Point at barcode or tap Capture.', 'ready');
            isProcessing = false;

            // Start Barcode Scanning Loop if ZXing is present
            if (codeReader && !isProcessing) {
                startBarcodeScanning();
            }

        } catch (err) {
            console.error('Camera Error:', err);
            let errMsg = 'Unable to access camera.';
            if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                errMsg = 'Camera permission denied. Please allow camera permissions in browser settings.';
            } else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
                errMsg = 'No camera device found on this system.';
            } else if (location.protocol !== 'https:' && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') {
                errMsg = 'Camera access requires HTTPS connection on remote domains.';
            }

            setStatus('Camera access failed', 'error');
            showCameraError(errMsg);
        }
    }

    // Stop Camera Tracks
    function stopCamera() {
        if (codeReader) {
            try { codeReader.reset(); } catch (e) {}
        }
        if (currentStream) {
            currentStream.getTracks().forEach(track => track.stop());
            currentStream = null;
        }
    }

    // Continuous Barcode Scanning
    function startBarcodeScanning() {
        if (!codeReader || !videoElem) return;

        codeReader.decodeFromVideoElement(videoElem, (result, err) => {
            if (result && !isProcessing) {
                const barcodeText = result.getText();
                if (!barcodeText || barcodeText.length < 6) {
                    return;
                }
                isProcessing = true;
                console.log('Barcode detected:', barcodeText);

                // Haptic feedback if supported
                if (navigator.vibrate) navigator.vibrate([100]);

                setStatus('Barcode detected: ' + barcodeText, 'busy');
                handleBarcodeDetected(barcodeText);
            }
        }).catch(err => {
            console.warn('Barcode decoding error:', err);
        });
    }

    // Handle Barcode API Lookup
    async function handleBarcodeDetected(barcode) {
        try {
            const response = await fetch(APP_BASE + '/api/barcode.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ barcode: barcode })
            });

            const data = await response.json();

            if (data.success && data.found && data.product) {
                setStatus('Product found in catalog!', 'ready');
                const labels = { barcode: 'Barcode Match', sku: 'SKU Match', search: 'Catalog Match' };
                renderProductResult(data.product, {
                    badgeText: labels[data.product.match_type] || 'Catalog Match',
                    badgeBg: 'bg-success',
                    reason: 'Matched ' + barcode
                });
            } else {
                setStatus(data.message || 'Product is not in the catalog.', 'error');
                showNotFoundResult(data.message || 'No catalog item matches ' + barcode + '.');
            }
        } catch (e) {
            console.error('Barcode API Error:', e);
            setStatus('Network error during barcode check', 'error');
            isProcessing = false;
        }
    }

    function compressImageSource(source) {
        const canvas = document.createElement('canvas');
        const maxDim = 1024;
        let w = source.videoWidth || source.naturalWidth || source.width || 640;
        let h = source.videoHeight || source.naturalHeight || source.height || 480;

        if (w > maxDim || h > maxDim) {
            if (w > h) {
                h = Math.round((h * maxDim) / w);
                w = maxDim;
            } else {
                w = Math.round((w * maxDim) / h);
                h = maxDim;
            }
        }

        canvas.width = w;
        canvas.height = h;
        canvas.getContext('2d').drawImage(source, 0, 0, w, h);
        return canvas.toDataURL('image/jpeg', 0.85);
    }

    async function recognizeBase64(base64Data) {
        setStatus('Identifying product with AI...', 'busy');

        const response = await fetch(APP_BASE + '/api/recognize.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ image: base64Data })
        });

        const data = await response.json();

        if (data.success && data.found && data.product) {
            setStatus('Product identified by AI!', 'ready');
            renderProductResult(data.product, {
                badgeText: (data.confidence || 90) + '% Confidence',
                badgeBg: 'bg-primary',
                reason: data.reason || 'Identified via AI vision catalog analysis'
            });
        } else {
            setStatus('Product not found in catalog', 'error');
            showNotFoundResult(data.message || 'AI could not confidently match this image to any catalog item.');
        }
    }

    // Capture Image and Send to AI Recognition Endpoint
    async function captureAndRecognize() {
        if (isProcessing) return;
        if (!videoElem || !videoElem.videoWidth) {
            setStatus('Camera frame is not ready', 'error');
            showNotFoundResult('Camera is not available. Enter a barcode below, or upload a product photo.');
            return;
        }
        isProcessing = true;

        setStatus('Capturing frame...', 'busy');

        try {
            await recognizeBase64(compressImageSource(videoElem));
        } catch (e) {
            console.error('AI Recognition Exception:', e);
            setStatus('AI Service Unavailable', 'error');
            showNotFoundResult('Unable to reach AI vision service. Please try again.');
        }
    }

    function recognizeUploadedFile(file) {
        if (!file || isProcessing) return;
        isProcessing = true;
        setStatus('Reading uploaded photo...', 'busy');

        const reader = new FileReader();
        reader.onerror = () => {
            isProcessing = false;
            setStatus('Could not read the photo', 'error');
        };
        reader.onload = () => {
            const image = new Image();
            image.onload = async () => {
                try {
                    await recognizeBase64(compressImageSource(image));
                } catch (e) {
                    console.error('AI Recognition Exception:', e);
                    setStatus('AI Service Unavailable', 'error');
                    showNotFoundResult('Unable to reach AI vision service. Please try again.');
                }
            };
            image.onerror = () => {
                isProcessing = false;
                setStatus('Uploaded file is not a readable image', 'error');
            };
            image.src = reader.result;
        };
        reader.readAsDataURL(file);
    }

    // Render Found Product Card
    function pauseLiveScan() {
        isProcessing = false;
        if (codeReader) {
            try { codeReader.reset(); } catch (e) {}
        }
    }

    function renderProductResult(product, meta = {}) {
        if (!resultContainer) return;
        pauseLiveScan();

        // Hide camera view, show result
        if (cameraWrapper) cameraWrapper.style.display = 'none';

        const imageSrc = product.image_url || product.image || 'assets/img/placeholder.png';
        const barcodeDisplay = product.barcode ? product.barcode : 'N/A';

        resultContainer.innerHTML = `
            <div class="product-result-card">
                <div class="product-result-img-wrapper">
                    <span class="confidence-badge ${meta.badgeBg || 'bg-success'}">
                        <i class="fas fa-check-circle me-1"></i> ${meta.badgeText || 'Matched'}
                    </span>
                    <img src="${imageSrc}" alt="${escapeHtml(product.name)}" class="product-result-img" onerror="this.src='https://via.placeholder.com/400x300?text=Product+Image'">
                </div>
                <div class="p-4">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h3 class="fw-bold text-dark mb-0">${escapeHtml(product.name)}</h3>
                    </div>
                    
                    <div class="price-tag mb-3">
                        RM ${product.price}
                    </div>

                    <div class="row g-2 mb-3 text-muted small">
                        <div class="col-6">
                            <i class="fas fa-barcode me-1"></i> <strong>Barcode:</strong> ${escapeHtml(barcodeDisplay)}
                        </div>
                        <div class="col-6">
                            <i class="fas fa-tag me-1"></i> <strong>SKU:</strong> ${escapeHtml(product.sku)}
                        </div>
                    </div>

                    <p class="text-secondary small mb-4">
                        ${escapeHtml(product.description || 'No description available for this item.')}
                    </p>

                    ${meta.reason ? `
                        <div class="alert alert-light border small text-muted mb-4 py-2 px-3 rounded-3">
                            <i class="fas fa-robot text-primary me-2"></i> <strong>AI Note:</strong> ${escapeHtml(meta.reason)}
                        </div>
                    ` : ''}

                    <div class="d-grid gap-2">
                        <button type="button" id="btn-scan-again" class="btn btn-primary-custom py-2.5">
                            <i class="fas fa-camera me-2"></i> Scan Another Product
                        </button>
                    </div>
                </div>
            </div>
        `;

        resultContainer.style.display = 'block';

        // Bind scan again button
        document.getElementById('btn-scan-again')?.addEventListener('click', resetScanner);
    }

    // Show Not Found Box
    function showNotFoundResult(message) {
        if (!resultContainer) return;
        pauseLiveScan();

        if (cameraWrapper) cameraWrapper.style.display = 'none';

        resultContainer.innerHTML = `
            <div class="card-custom p-4 text-center">
                <div class="mb-3 text-warning display-4">
                    <i class="fas fa-search-minus"></i>
                </div>
                <h4 class="fw-bold mb-2">Product Not Found</h4>
                <p class="text-secondary small mb-4">${escapeHtml(message)}</p>
                <button type="button" id="btn-scan-again" class="btn btn-outline-primary rounded-pill px-4">
                    <i class="fas fa-redo me-2"></i> Try Scanning Again
                </button>
            </div>
        `;

        resultContainer.style.display = 'block';
        document.getElementById('btn-scan-again')?.addEventListener('click', resetScanner);
    }

    // Camera Error Box
    function showCameraError(message) {
        if (cameraWrapper) {
            cameraWrapper.innerHTML = `
                <div class="d-flex flex-column align-items-center justify-content-center h-100 p-4 text-center text-white bg-dark">
                    <i class="fas fa-exclamation-triangle text-warning display-4 mb-3"></i>
                    <h5 class="fw-bold">Camera Unavailable</h5>
                    <p class="small text-light mb-4">${escapeHtml(message)}</p>
                    <button class="btn btn-sm btn-outline-light rounded-pill" onclick="location.reload()">
                        <i class="fas fa-sync me-2"></i> Retry Permission
                    </button>
                </div>
            `;
        }
    }

    // Reset Scanner to Live Camera Mode
    function resetScanner() {
        if (resultContainer) resultContainer.style.display = 'none';
        if (cameraWrapper) cameraWrapper.style.display = 'block';
        isProcessing = false;
        startCamera();
    }

    // Toggle Camera (Front/Rear)
    function switchCamera() {
        currentFacingMode = (currentFacingMode === 'environment') ? 'user' : 'environment';
        startCamera();
    }

    // Escape HTML Helper
    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m]));
    }

    // Event Listeners
    if (btnCapture) btnCapture.addEventListener('click', captureAndRecognize);
    if (btnSwitchCamera) btnSwitchCamera.addEventListener('click', switchCamera);
    if (btnRetake) btnRetake.addEventListener('click', resetScanner);
    if (manualForm) {
        manualForm.addEventListener('submit', (event) => {
            event.preventDefault();
            const code = (manualInput?.value || '').trim();
            if (!code || isProcessing) return;
            isProcessing = true;
            handleBarcodeDetected(code);
        });
    }
    document.querySelectorAll('.sample-barcode').forEach((button) => {
        button.addEventListener('click', () => {
            const code = button.dataset.code || '';
            if (manualInput) manualInput.value = code;
            if (!code || isProcessing) return;
            isProcessing = true;
            handleBarcodeDetected(code);
        });
    });
    if (fileInput) {
        fileInput.addEventListener('change', () => {
            const file = fileInput.files && fileInput.files[0];
            if (file) recognizeUploadedFile(file);
            fileInput.value = '';
        });
    }

    // Initial Start
    startCamera();
});
