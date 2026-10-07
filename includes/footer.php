<?php
/**
 * AI Smart Shopping Assistant
 * Common Footer Template
 */
?>
    <footer class="mt-auto py-4 bg-white border-top">
        <div class="container text-center">
            <div class="row align-items-center gy-3">
                <div class="col-md-6 text-md-start">
                    <p class="mb-0 text-muted small">
                        &copy; <?= date('Y') ?> <strong><?= e(APP_NAME) ?></strong>. Built for Mobile & Retail Computer Vision.
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <span class="badge bg-light text-dark border me-1"><i class="fab fa-php text-primary"></i> PHP 8.3+</span>
                    <span class="badge bg-light text-dark border me-1"><i class="fas fa-database text-success"></i> MariaDB PDO</span>
                    <span class="badge bg-light text-dark border"><i class="fas fa-microchip text-purple"></i> AI Computer Vision</span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
