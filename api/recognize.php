<?php
/**
 * AI Smart Shopping Assistant
 * AI Vision Product Recognition API Endpoint
 */

require_once __DIR__ . '/../includes/init.php';

header('Content-Type: application/json; charset=utf-8');

// Accept JSON body with base64 image string
$input_raw = file_get_contents('php://input');
$input_json = json_decode($input_raw, true);

$base64_image = $input_json['image'] ?? $_POST['image'] ?? null;

if (empty($base64_image)) {
    echo json_encode([
        'success' => false,
        'found'   => false,
        'message' => 'Product image is required for AI identification.'
    ]);
    exit;
}

// Call AI Provider via backend abstraction layer
$ai_result = ai_chat($base64_image);

if (!$ai_result['success']) {
    echo json_encode([
        'success' => false,
        'found'   => false,
        'message' => $ai_result['message'] ?? 'Unable to identify product image.'
    ]);
    exit;
}

$ai_data   = $ai_result['data'] ?? [];
$ai_sku    = $ai_data['sku'] ?? null;
$ai_name   = $ai_data['product_name'] ?? null;
$confidence = $ai_data['confidence'] ?? 0.85;
$reason    = $ai_data['reason'] ?? '';

// Perform Multi-Strategy Match against MySQL catalog
$match = match_product_from_ai($ai_sku, $ai_name, $reason, (float)$confidence);

if ($match['matched'] && !empty($match['product'])) {
    $product = $match['product'];
    $image_url = !empty($product['image_path']) ? APP_URL . '/' . ltrim($product['image_path'], '/') : null;

    echo json_encode([
        'success'    => true,
        'found'      => true,
        'message'    => 'Product identified successfully by AI.',
        'confidence' => round($confidence * 100),
        'reason'     => $reason,
        'match_mode' => $match['method'],
        'product'    => [
            'id'          => (int)$product['id'],
            'sku'         => $product['sku'],
            'barcode'     => $product['barcode'],
            'name'        => $product['name'],
            'price'       => number_format((float)$product['price'], 2, '.', ''), // Strict MySQL price!
            'description' => $product['description'],
            'image'       => $product['image_path'],
            'image_url'   => $image_url,
        ]
    ]);
} else {
    echo json_encode([
        'success' => true,
        'found'   => false,
        'message' => 'AI scanned the image, but could not find a matching product in the store catalog.'
    ]);
}
