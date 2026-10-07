<?php
/**
 * AI Smart Shopping Assistant
 * Admin Product CRUD Management Page
 */

require_once __DIR__ . '/../includes/init.php';

// Enforce Admin Access
require_admin();

$error = '';
$success = '';

// Handle Product Operations (Add / Edit / Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'CSRF validation failed. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'save_product') {
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            $sku = trim($_POST['sku'] ?? '');
            $barcode = trim($_POST['barcode'] ?? '');
            $name = trim($_POST['name'] ?? '');
            $detect_keywords = trim($_POST['detect_keywords'] ?? '');
            $price = floatval($_POST['price'] ?? 0);
            $description = trim($_POST['description'] ?? '');

            if (empty($sku) || empty($name) || $price < 0) {
                $error = 'SKU, Product Name, and a valid positive Price are required.';
            } else {
                // Check for duplicate SKU
                $existing_sku = db_fetch_one("SELECT id FROM products WHERE sku = ? AND id != ?", [$sku, $id]);
                if ($existing_sku) {
                    $error = 'The SKU "' . $sku . '" is already in use by another product.';
                } else {
                    $image_path = null;
                    
                    // Keep existing image if editing
                    if ($id > 0) {
                        $curr_prod = get_product_by_id($id);
                        $image_path = $curr_prod['image_path'] ?? null;
                    }

                    // Process new image upload if provided
                    if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                        $upload_res = upload_product_image($_FILES['image']);
                        if (!$upload_res['success']) {
                            $error = $upload_res['error'];
                        } else {
                            // Delete old image if updating
                            if ($id > 0 && !empty($image_path)) {
                                delete_product_image($image_path);
                            }
                            $image_path = $upload_res['path'];
                        }
                    }

                    if (empty($error)) {
                        $product_data = [
                            'sku'             => $sku,
                            'barcode'         => $barcode,
                            'name'            => $name,
                            'detect_keywords' => $detect_keywords,
                            'price'           => $price,
                            'description'     => $description,
                            'image_path'      => $image_path
                        ];

                        if ($id > 0) {
                            if (update_product($id, $product_data)) {
                                $_SESSION['flash_success'] = 'Product "' . $name . '" updated successfully.';
                                header('Location: ' . APP_URL . '/admin/products.php');
                                exit;
                            } else {
                                $error = 'Failed to update product in database.';
                            }
                        } else {
                            if (create_product($product_data)) {
                                $_SESSION['flash_success'] = 'New product "' . $name . '" added successfully.';
                                header('Location: ' . APP_URL . '/admin/products.php');
                                exit;
                            } else {
                                $error = 'Failed to save product to database.';
                            }
                        }
                    }
                }
            }
        } elseif ($action === 'delete_product') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                if (delete_product($id)) {
                    $_SESSION['flash_success'] = 'Product deleted successfully.';
                    header('Location: ' . APP_URL . '/admin/products.php');
                    exit;
                } else {
                    $error = 'Failed to delete product.';
                }
            }
        }
    }
}

$search = trim($_GET['q'] ?? '');
$products = get_all_products($search);
$edit_product = null;

if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $edit_product = get_product_by_id($edit_id);
}

$page_title = 'Manage Products - ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h2 class="fw-bold mb-1">
                <i class="fas fa-boxes text-primary me-2"></i> Manage Product Catalog
            </h2>
            <p class="text-secondary small mb-0">Add, edit, or remove items in store inventory</p>
        </div>

        <div class="d-flex gap-2">
            <button type="button" class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#productModal" onclick="resetForm()">
                <i class="fas fa-plus me-1"></i> Add Product
            </button>
            <a href="<?= APP_URL ?>/admin/settings.php" class="btn btn-outline-secondary">
                <i class="fas fa-cog me-1"></i> AI Settings
            </a>
        </div>
    </div>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger rounded-4 shadow-sm mb-4">
            <i class="fas fa-exclamation-circle me-2"></i> <?= e($error) ?>
        </div>
    <?php endif; ?>

    <!-- Search Bar -->
    <div class="card-custom p-3 mb-4">
        <form method="GET" action="products.php" class="row g-2">
            <div class="col-md-10">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" name="q" class="form-control border-start-0" placeholder="Search product name, SKU, barcode, or detection keywords..." value="<?= e($search) ?>">
                </div>
            </div>
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-primary-custom">Filter</button>
            </div>
        </form>
    </div>

    <!-- Products Table -->
    <div class="card-custom overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 70px;">Image</th>
                        <th>Product Info</th>
                        <th>SKU / Barcode</th>
                        <th>Price</th>
                        <th>Detection Keywords</th>
                        <th class="text-end" style="width: 140px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fas fa-box-open fs-2 mb-2 d-block text-secondary"></i>
                                No products found in the catalog.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $p): ?>
                            <?php 
                                $img = !empty($p['image_path']) ? APP_URL . '/' . ltrim($p['image_path'], '/') : 'https://via.placeholder.com/80?text=No+Img';
                            ?>
                            <tr>
                                <td>
                                    <img src="<?= e($img) ?>" alt="<?= e($p['name']) ?>" class="rounded-3" style="width: 50px; height: 50px; object-fit: cover;" onerror="this.src='https://via.placeholder.com/80?text=No+Img'">
                                </td>
                                <td>
                                    <strong class="text-dark d-block"><?= e($p['name']) ?></strong>
                                    <span class="text-muted extra-small"><?= e(mb_strimwidth($p['description'] ?? '', 0, 75, '...')) ?></span>
                                </td>
                                <td>
                                    <div class="small">
                                        <span class="badge bg-light text-dark border">SKU: <?= e($p['sku']) ?></span>
                                    </div>
                                    <?php if (!empty($p['barcode'])): ?>
                                        <div class="extra-small text-muted mt-1">
                                            <i class="fas fa-barcode me-1"></i><?= e($p['barcode']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="fw-bold text-primary">RM <?= number_format((float)$p['price'], 2) ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-purple-subtle text-purple border small"><?= e($p['detect_keywords'] ?: 'None') ?></span>
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-primary me-1 btn-edit-product" data-product="<?= e(json_encode($p, JSON_UNESCAPED_UNICODE)) ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" action="products.php" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete_product">
                                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add / Edit Product Modal -->
<div class="modal fade" id="productModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="modalTitle">
                    <i class="fas fa-box text-primary me-2"></i> Add Product
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="products.php" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save_product">
                    <input type="hidden" name="id" id="prod_id" value="0">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Product Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="prod_name" class="form-control" required placeholder="e.g. Hong Thai Herbal Inhaler">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Price (RM) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" name="price" id="prod_price" class="form-control" required placeholder="0.00">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">SKU <span class="text-danger">*</span></label>
                            <input type="text" name="sku" id="prod_sku" class="form-control" required placeholder="e.g. SKU-INH-001">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Barcode (EAN-13, Code128, etc.)</label>
                            <input type="text" name="barcode" id="prod_barcode" class="form-control" placeholder="e.g. 8857200535366">
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold small">AI Detection Keywords (Comma Separated)</label>
                            <input type="text" name="detect_keywords" id="prod_detect_keywords" class="form-control" placeholder="e.g. hong thai, green bottle, herbal inhaler">
                            <div class="form-text">Used by AI vision engine to match visual product features.</div>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold small">Description</label>
                            <textarea name="description" id="prod_description" rows="3" class="form-control" placeholder="Detailed product specifications..."></textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold small">Product Image (JPG, PNG, WEBP, max 5MB)</label>
                            <input type="file" name="image" id="prod_image" class="form-control" accept="image/jpeg,image/png,image/webp">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-custom px-4">Save Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function resetForm() {
    document.getElementById('modalTitle').innerText = 'Add New Product';
    document.getElementById('prod_id').value = '0';
    document.getElementById('prod_name').value = '';
    document.getElementById('prod_price').value = '';
    document.getElementById('prod_sku').value = '';
    document.getElementById('prod_barcode').value = '';
    document.getElementById('prod_detect_keywords').value = '';
    document.getElementById('prod_description').value = '';
    document.getElementById('prod_image').value = '';
}

function editProduct(p) {
    document.getElementById('modalTitle').innerText = 'Edit Product #' + p.id;
    document.getElementById('prod_id').value = p.id;
    document.getElementById('prod_name').value = p.name || '';
    document.getElementById('prod_price').value = p.price || '';
    document.getElementById('prod_sku').value = p.sku || '';
    document.getElementById('prod_barcode').value = p.barcode || '';
    document.getElementById('prod_detect_keywords').value = p.detect_keywords || '';
    document.getElementById('prod_description').value = p.description || '';
    document.getElementById('prod_image').value = '';

    var modal = new bootstrap.Modal(document.getElementById('productModal'));
    modal.show();
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.btn-edit-product').forEach(function(btn) {
        btn.addEventListener('click', function() {
            editProduct(JSON.parse(btn.dataset.product));
        });
    });
});

<?php if ($edit_product): ?>
document.addEventListener('DOMContentLoaded', function() {
    editProduct(<?= json_encode($edit_product, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>);
});
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
