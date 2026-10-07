<?php
/**
 * AI Smart Shopping Assistant
 * Product Catalog Page
 */

require_once __DIR__ . '/includes/init.php';

// Auto-check installation
if (!is_db_installed()) {
    header('Location: ' . APP_URL . '/install.php');
    exit;
}

$page_title = 'Product Catalog - ' . APP_NAME;

$search = trim($_GET['q'] ?? '');
$products = get_all_products($search);

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h2 class="fw-bold mb-1">
                <i class="fas fa-boxes text-primary me-2"></i> Store Product Catalog
            </h2>
            <p class="text-secondary small mb-0">Browse all registered items available for barcode and AI recognition</p>
        </div>

        <form method="GET" action="products.php" class="d-flex gap-2" style="max-width: 380px;">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0">
                    <i class="fas fa-search text-muted"></i>
                </span>
                <input type="text" name="q" class="form-control border-start-0 ps-0" placeholder="Search name, SKU, barcode..." value="<?= e($search) ?>">
                <?php if ($search !== ''): ?>
                    <a href="products.php" class="btn btn-outline-secondary">Clear</a>
                <?php else: ?>
                    <button type="submit" class="btn btn-primary-custom">Search</button>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <?php if (empty($products)): ?>
        <div class="card-custom p-5 text-center my-4">
            <div class="display-4 text-muted mb-3">
                <i class="fas fa-box-open"></i>
            </div>
            <h4 class="fw-bold mb-2">No Products Found</h4>
            <p class="text-secondary">
                <?= $search !== '' ? 'No items matched your search criteria "' . e($search) . '".' : 'The catalog is currently empty. Administrators can add products in the admin panel.' ?>
            </p>
            <?php if ($search !== ''): ?>
                <a href="products.php" class="btn btn-outline-primary rounded-pill px-4 mt-2">View All Products</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($products as $p): ?>
                <?php 
                    $img_src = !empty($p['image_path']) ? APP_URL . '/' . ltrim($p['image_path'], '/') : 'https://via.placeholder.com/400x300?text=No+Image';
                ?>
                <div class="col-sm-6 col-lg-4 col-xl-3">
                    <div class="card-custom h-100 d-flex flex-column">
                        <div class="position-relative">
                            <img src="<?= e($img_src) ?>" alt="<?= e($p['name']) ?>" class="product-grid-img" onerror="this.src='https://via.placeholder.com/400x300?text=No+Image'">
                            <span class="position-absolute top-0 end-0 m-2 badge bg-primary fs-6 font-monospace">
                                RM <?= number_format((float)$p['price'], 2) ?>
                            </span>
                        </div>
                        <div class="p-3 d-flex flex-column flex-grow-1">
                            <h5 class="fw-bold text-dark mb-1 text-truncate" title="<?= e($p['name']) ?>">
                                <?= e($p['name']) ?>
                            </h5>
                            
                            <div class="mb-2 text-muted extra-small">
                                <span class="badge bg-light text-dark border me-1">SKU: <?= e($p['sku']) ?></span>
                                <?php if (!empty($p['barcode'])): ?>
                                    <span class="badge bg-light text-dark border"><i class="fas fa-barcode me-1"></i><?= e($p['barcode']) ?></span>
                                <?php endif; ?>
                            </div>

                            <p class="text-secondary small flex-grow-1 mb-3">
                                <?= e(mb_strimwidth($p['description'] ?? '', 0, 95, '...')) ?>
                            </p>

                            <?php if (!empty($p['detect_keywords'])): ?>
                                <div class="extra-small text-muted border-top pt-2">
                                    <i class="fas fa-tags text-primary me-1"></i> Keywords: <?= e($p['detect_keywords']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
