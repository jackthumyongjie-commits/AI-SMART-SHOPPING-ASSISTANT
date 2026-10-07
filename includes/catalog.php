<?php
/**
 * AI Smart Shopping Assistant
 * Product Catalog Data Access & AI Product Matching Engine
 */

if (!defined('APP_RUNNING')) {
    exit('Direct access denied.');
}

/**
 * Get product by Primary Key ID
 */
function get_product_by_id(int $id): ?array {
    return db_fetch_one("SELECT * FROM products WHERE id = ?", [$id]);
}

/**
 * Get product by exact SKU
 */
function get_product_by_sku(string $sku): ?array {
    $sku = trim($sku);
    if (empty($sku)) return null;
    return db_fetch_one("SELECT * FROM products WHERE sku = ?", [$sku]);
}

/**
 * Get product by exact Barcode
 */
function get_product_by_barcode(string $barcode): ?array {
    $barcode = trim($barcode);
    if (empty($barcode)) return null;
    return db_fetch_one("SELECT * FROM products WHERE barcode = ?", [$barcode]);
}

/**
 * Get all products with optional search term (name, sku, barcode, keywords)
 */
function get_all_products(string $search = ''): array {
    $search = trim($search);
    if ($search === '') {
        return db_fetch_all("SELECT * FROM products ORDER BY id DESC");
    }

    $term = "%{$search}%";
    return db_fetch_all(
        "SELECT * FROM products 
         WHERE name LIKE ? OR sku LIKE ? OR barcode LIKE ? OR detect_keywords LIKE ? OR description LIKE ?
         ORDER BY id DESC",
        [$term, $term, $term, $term, $term]
    );
}

/**
 * Create a new product
 */
function create_product(array $data): ?int {
    $sql = "INSERT INTO products (sku, barcode, name, detect_keywords, price, description, image_path)
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $params = [
        trim($data['sku']),
        !empty($data['barcode']) ? trim($data['barcode']) : null,
        trim($data['name']),
        !empty($data['detect_keywords']) ? trim($data['detect_keywords']) : null,
        floatval($data['price']),
        !empty($data['description']) ? trim($data['description']) : null,
        !empty($data['image_path']) ? trim($data['image_path']) : null,
    ];

    $stmt = db_query($sql, $params);
    if ($stmt) {
        $pdo = get_db_connection();
        return (int)$pdo->lastInsertId();
    }
    return null;
}

/**
 * Update existing product
 */
function update_product(int $id, array $data): bool {
    $sql = "UPDATE products 
            SET sku = ?, barcode = ?, name = ?, detect_keywords = ?, price = ?, description = ?, image_path = ?
            WHERE id = ?";
    $params = [
        trim($data['sku']),
        !empty($data['barcode']) ? trim($data['barcode']) : null,
        trim($data['name']),
        !empty($data['detect_keywords']) ? trim($data['detect_keywords']) : null,
        floatval($data['price']),
        !empty($data['description']) ? trim($data['description']) : null,
        !empty($data['image_path']) ? trim($data['image_path']) : null,
        $id
    ];

    $stmt = db_query($sql, $params);
    return $stmt !== null;
}

/**
 * Delete product by ID (cleans up image file if present)
 */
function delete_product(int $id): bool {
    $product = get_product_by_id($id);
    if ($product && !empty($product['image_path'])) {
        delete_product_image($product['image_path']);
    }

    $stmt = db_query("DELETE FROM products WHERE id = ?", [$id]);
    return $stmt !== null;
}

/**
 * Normalize string for matching (lowercase, strip non-alphanumeric)
 */
function normalize_text(string $str): string {
    $str = mb_strtolower($str, 'UTF-8');
    $str = preg_replace('/[^\p{L}\p{N}\s]/u', '', $str);
    return trim(preg_replace('/\s+/', ' ', $str));
}

/**
 * Words that appear on many packages and must not decide a match by themselves.
 */
function match_stopwords(): array {
    return [
        'water', 'drink', 'bottle', 'green', 'milk', 'natural', 'original', 'energy',
        'chocolate', 'coffee', 'herbal', 'thai', 'product', 'package', 'label',
        'white', 'black', 'blue', 'gold', 'mix', 'traditional', 'instant', 'dairy',
        'fresh', 'premium', 'classic', 'with', 'from', 'this', 'that', 'item'
    ];
}

function evidence_has_token(string $evidence, string $token): bool {
    if ($evidence === '' || $token === '') {
        return false;
    }
    if (str_contains($token, ' ')) {
        return str_contains($evidence, $token);
    }
    return preg_match('/(?:^|\s)' . preg_quote($token, '/') . '(?:\s|$)/u', $evidence) === 1;
}

function clean_ai_text(?string $value): string {
    $value = trim((string)$value);
    if ($value === '' || preg_match('/^(null|none|n\/a|unknown)$/i', $value)) {
        return '';
    }
    return $value;
}

/**
 * Distinctive words and keyword phrases for one catalog product.
 *
 * @return array<int, string>
 */
function product_match_tokens(array $product): array {
    $stop = array_flip(match_stopwords());
    $tokens = [];

    $blob = normalize_text(($product['name'] ?? '') . ' ' . ($product['sku'] ?? '') . ' ' . ($product['detect_keywords'] ?? ''));
    foreach (preg_split('/\s+/', $blob) ?: [] as $part) {
        if (mb_strlen($part) >= 4 && !isset($stop[$part])) {
            $tokens[$part] = true;
        }
    }

    foreach (explode(',', (string)($product['detect_keywords'] ?? '')) as $keyword) {
        $keyword = normalize_text($keyword);
        if (mb_strlen($keyword) >= 4 && !isset($stop[$keyword])) {
            $tokens[$keyword] = true;
        }
    }

    return array_keys($tokens);
}

/**
 * Match what the camera read against the catalog.
 * A product wins only when the photo text contains a word that belongs to that product alone.
 * Returns array with keys: 'matched' (bool), 'product' (array|null), 'method' (string)
 */
function match_product_from_ai(?string $ai_sku, ?string $ai_name, ?string $ai_reason = '', float $confidence = 0): array {
    $products = get_all_products();
    if (empty($products)) {
        return ['matched' => false, 'product' => null, 'method' => 'no_catalog'];
    }

    $ai_sku = clean_ai_text($ai_sku);
    $ai_name = clean_ai_text($ai_name);
    $ai_reason = clean_ai_text($ai_reason);
    $evidence = normalize_text($ai_name . ' ' . $ai_reason);

    $token_owners = [];
    $product_tokens = [];
    foreach ($products as $product) {
        $tokens = product_match_tokens($product);
        $product_tokens[$product['id']] = $tokens;
        foreach ($tokens as $token) {
            $token_owners[$token][] = $product['id'];
        }
    }

    $scores = [];
    foreach ($products as $product) {
        $score = 0;
        foreach ($product_tokens[$product['id']] as $token) {
            if (count($token_owners[$token]) !== 1 || !evidence_has_token($evidence, $token)) {
                continue;
            }
            $score += str_contains($token, ' ') ? 3 : 1;
        }
        $scores[$product['id']] = $score;
    }

    $best_id = null;
    $best_score = 0;
    $second_score = 0;
    foreach ($scores as $id => $score) {
        if ($score > $best_score) {
            $second_score = $best_score;
            $best_score = $score;
            $best_id = $id;
        } elseif ($score > $second_score) {
            $second_score = $score;
        }
    }

    $by_id = [];
    foreach ($products as $product) {
        $by_id[$product['id']] = $product;
    }

    if ($best_id !== null && $best_score >= 1 && $best_score > $second_score) {
        return ['matched' => true, 'product' => $by_id[$best_id], 'method' => 'visible_text'];
    }

    // Accept a printed SKU only when the model is confident and the visible text agrees.
    if ($ai_sku !== '' && $confidence >= 0.75) {
        foreach ($products as $product) {
            if (strcasecmp($product['sku'], $ai_sku) !== 0) {
                continue;
            }
            if (($scores[$product['id']] ?? 0) >= 1) {
                return ['matched' => true, 'product' => $product, 'method' => 'sku'];
            }
        }
    }

    return ['matched' => false, 'product' => null, 'method' => 'none'];
}
