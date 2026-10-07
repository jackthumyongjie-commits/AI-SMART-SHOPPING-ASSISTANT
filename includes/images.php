<?php
/**
 * AI Smart Shopping Assistant
 * Secure File Upload & Image Processing Utility
 */

if (!defined('APP_RUNNING')) {
    exit('Direct access denied.');
}

/**
 * Handle secure product image upload
 * Returns array ['success' => bool, 'path' => string, 'error' => string]
 */
function upload_product_image(array $file): array {
    if (empty($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'No file uploaded or upload error occurred.'];
    }

    // Check size limit (5MB)
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        return ['success' => false, 'error' => 'File size exceeds maximum limit of 5MB.'];
    }

    // Validate MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime_type, ALLOWED_IMAGE_TYPES, true)) {
        return ['success' => false, 'error' => 'Invalid image format. Allowed formats: JPG, PNG, WEBP.'];
    }

    // Validate extension
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_IMAGE_EXTS, true)) {
        return ['success' => false, 'error' => 'Invalid file extension.'];
    }

    // Verify it's a real image using getimagesize
    $img_info = @getimagesize($file['tmp_name']);
    if ($img_info === false) {
        return ['success' => false, 'error' => 'Uploaded file is not a valid image.'];
    }

    // Ensure target directory exists
    if (!is_dir(PRODUCT_UPLOADS_DIR)) {
        mkdir(PRODUCT_UPLOADS_DIR, 0755, true);
    }

    // Generate safe random filename
    $random_bytes = bin2hex(random_bytes(8));
    $new_filename = 'p_' . time() . '_' . $random_bytes . '.' . $ext;
    $target_filepath = PRODUCT_UPLOADS_DIR . '/' . $new_filename;

    if (!move_uploaded_file($file['tmp_name'], $target_filepath)) {
        return ['success' => false, 'error' => 'Failed to save uploaded image file on server.'];
    }

    // Relative web path for DB storage
    $relative_path = 'uploads/products/' . $new_filename;
    return ['success' => true, 'path' => $relative_path, 'error' => ''];
}

/**
 * Safely delete product image file
 */
function delete_product_image(?string $relative_path): void {
    if (empty($relative_path)) return;

    // Prevent directory traversal
    $filename = basename($relative_path);
    $full_path = PRODUCT_UPLOADS_DIR . '/' . $filename;

    if (file_exists($full_path) && is_file($full_path)) {
        @unlink($full_path);
    }
}

/**
 * Generate elegant placeholder image for sample products if missing
 */
function create_sample_product_images(): void {
    if (!is_dir(PRODUCT_UPLOADS_DIR)) {
        mkdir(PRODUCT_UPLOADS_DIR, 0755, true);
    }

    $samples = [
        'sample_inhaler.png'  => ['title' => 'Hong Thai Inhaler',  'bg' => [46, 117, 89],   'fg' => [255, 255, 255]],
        'sample_coffee.png'   => ['title' => 'Nescafé Coffee',     'bg' => [180, 40, 40],   'fg' => [255, 255, 255]],
        'sample_redbull.png'  => ['title' => 'Red Bull Drink',    'bg' => [218, 165, 32],  'fg' => [15, 23, 42]],
        'sample_chocolate.png'=> ['title' => 'Cadbury Milk',      'bg' => [74, 20, 140],   'fg' => [255, 255, 255]],
        'sample_greentea.png' => ['title' => 'ChaTraMue Tea',     'bg' => [34, 139, 34],   'fg' => [255, 255, 255]],
        'sample_water.png'    => ['title' => 'Evian Water',       'bg' => [70, 130, 180],  'fg' => [255, 255, 255]],
    ];

    foreach ($samples as $filename => $info) {
        $filepath = PRODUCT_UPLOADS_DIR . '/' . $filename;
        if (!file_exists($filepath) && function_exists('imagecreatetruecolor')) {
            $im = imagecreatetruecolor(400, 400);
            $bg = imagecolorallocate($im, $info['bg'][0], $info['bg'][1], $info['bg'][2]);
            $fg = imagecolorallocate($im, $info['fg'][0], $info['fg'][1], $info['fg'][2]);
            imagefill($im, 0, 0, $bg);

            // Draw clean rounded aesthetic box
            $box_color = imagecolorallocatealpha($im, 255, 255, 255, 100);
            imagefilledrectangle($im, 40, 40, 360, 360, $box_color);

            // Write centered text
            imagestring($im, 5, 110, 190, $info['title'], $fg);
            imagestring($im, 3, 140, 220, "AI Smart Catalog", $fg);

            imagepng($im, $filepath);
            imagedestroy($im);
        }
    }
}
