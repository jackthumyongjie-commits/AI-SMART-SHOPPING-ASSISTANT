<?php
/**
 * AI Smart Shopping Assistant
 * Barcode Lookup API Endpoint
 */

require_once __DIR__ . '/../includes/init.php';

header('Content-Type: application/json; charset=utf-8');

// Accept JSON body or GET/POST parameter
$input_raw = file_get_contents('php://input');
$input_json = json_decode($input_raw, true);

$barcode = trim($input_json['barcode'] ?? $_REQUEST['barcode'] ?? '');

if (empty($barcode)) {
    echo json_encode([
        'success' => false,
        'found'   => false,
        'message' => 'Barcode input is required.'
    ]);
    exit;
}

// Clean barcode string for exact code lookup
$clean_barcode = preg_replace('/[^a-zA-Z0-9]/', '', $barcode);
$match_type = 'barcode';

// Query catalog by barcode, then SKU
$product = $clean_barcode !== '' ? get_product_by_barcode($clean_barcode) : null;
if (!$product && $barcode !== $clean_barcode) {
    $product = get_product_by_barcode($barcode);
}

if (!$product) {
    $product = $clean_barcode !== '' ? get_product_by_sku($clean_barcode) : null;
    if ($product) {
        $match_type = 'sku';
    }
}
if (!$product && $barcode !== $clean_barcode) {
    $product = get_product_by_sku($barcode);
    if ($product) {
        $match_type = 'sku';
    }
}

// Name lookup is only for typed words. A numeric barcode must match exactly,
// otherwise a bad camera read can land on whichever product shares those digits.
if (!$product && preg_match('/[a-zA-Z]/', $barcode)) {
    $matches = get_all_products($barcode);
    if (count($matches) === 1) {
        $product = $matches[0];
        $match_type = 'search';
    } elseif (count($matches) > 1) {
        echo json_encode([
            'success' => true,
            'found'   => false,
            'message' => 'Several products match "' . $barcode . '". Enter a full barcode, SKU, or a more specific name.'
        ]);
        exit;
    }
}

if ($product) {
    // Format response
    $image_url = !empty($product['image_path']) ? APP_URL . '/' . ltrim($product['image_path'], '/') : null;

    echo json_encode([
        'success' => true,
        'found'   => true,
        'message' => 'Product found in store catalog.',
        'product' => [
            'id'          => (int)$product['id'],
            'sku'         => $product['sku'],
            'barcode'     => $product['barcode'],
            'name'        => $product['name'],
            'price'       => number_format((float)$product['price'], 2, '.', ''),
            'description' => $product['description'],
            'image'       => $product['image_path'],
            'image_url'   => $image_url,
            'match_type'  => $match_type
        ]
    ]);
} else {
    echo json_encode([
        'success' => true,
        'found'   => false,
        'message' => 'Barcode detected, but product is not in store catalog.'
    ]);
}
