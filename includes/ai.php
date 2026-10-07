<?php
/**
 * AI Smart Shopping Assistant
 * Multi-Provider AI Vision & Chat Abstraction Layer
 * Supports Agnes AI (OpenAI-compatible) and Google Gemini Vision APIs
 */

if (!defined('APP_RUNNING')) {
    exit('Direct access denied.');
}

/**
 * System Prompt for Product Recognition Vision Task
 */
function get_ai_system_prompt(): string {
    return <<<PROMPT
You read retail product packages from a photo.
Look only at this image. Transcribe the brand and product words that are actually visible.
Do not guess a product and do not invent a SKU.

Return JSON only:
{
    "sku": null,
    "product_name": "visible brand and product words, or null",
    "confidence": 0.0,
    "reason": "container shape, colors, and the words you can read"
}

Rules:
1. product_name must be words printed on this package, not a product you merely think it might be.
2. If the label is blurry, missing, or this is not a packaged product, set product_name to null and confidence to 0.
3. sku must be null unless that exact code is printed on the package.
4. confidence is a number from 0.00 to 1.00 based on how readable the label is.
PROMPT;
}

/**
 * Main AI Chat / Vision entry point
 * Accepts base64 encoded image string (e.g. data:image/jpeg;base64,...)
 */
function ai_chat(?string $base64_image = null): array {
    $provider = get_setting('ai_provider', 'agnes');

    if ($provider === 'gemini') {
        return call_gemini_vision_api($base64_image);
    } else {
        return call_openai_vision_api($base64_image);
    }
}

/**
 * Test AI Connection from Admin Settings
 */
function test_ai_connection(): array {
    $provider = get_setting('ai_provider', 'agnes');
    $api_url  = get_setting($provider . '_api_url');
    $api_key  = get_setting($provider . '_api_key');
    $model    = get_setting($provider . '_model');

    if (empty($api_key)) {
        return [
            'success' => false,
            'message' => 'API Key is missing. Please enter and save your API key.'
        ];
    }

    $models = array_values(array_filter(array_map('trim', array_merge(
        [$model],
        explode(',', get_setting($provider . '_fallback_models', ''))
    ))));

    $last = ['success' => false, 'message' => 'No model is configured.'];
    foreach ($models as $candidate) {
        $last = $provider === 'gemini'
            ? test_gemini_connection($api_url, $api_key, $candidate)
            : test_openai_connection($api_url, $api_key, $candidate);
        if (!empty($last['success'])) {
            return $last;
        }
        if (!preg_match('/HTTP code (429|500|503)|timed out|high demand|UNAVAILABLE/i', $last['message'] ?? '')) {
            return $last;
        }
    }

    return $last;
}

/**
 * Call OpenAI-compatible / Agnes Vision API
 */
function call_openai_vision_api(?string $base64_image): array {
    $api_url = rtrim(get_setting('agnes_api_url', 'https://apihub.agnes-ai.com/v1'), '/');
    $api_key = get_setting('agnes_api_key', '');
    $primary_model = get_setting('agnes_model', 'agnes-2.5-flash');
    $fallbacks_str = get_setting('agnes_fallback_models', '');

    $models_to_try = array_filter(array_map('trim', array_merge([$primary_model], explode(',', $fallbacks_str))));

    if (empty($api_key)) {
        return ['success' => false, 'message' => 'AI API key not configured in Admin Settings.'];
    }

    $system_prompt = get_ai_system_prompt();

    // Prepare message payload
    $content = [];
    $content[] = ['type' => 'text', 'text' => $system_prompt];

    if ($base64_image) {
        $content[] = [
            'type' => 'image_url',
            'image_url' => ['url' => $base64_image]
        ];
    }

    $endpoint = $api_url . '/chat/completions';

    foreach ($models_to_try as $model) {
        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'user', 'content' => $content]
            ],
            'temperature' => 0.2,
            'response_format' => ['type' => 'json_object']
        ];

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $api_key
            ],
            CURLOPT_TIMEOUT => 45,
            CURLOPT_SSL_VERIFYPEER => true
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_err = curl_error($ch);
        curl_close($ch);

        if ($curl_err) {
            error_log("OpenAI cURL Error ({$model}): " . $curl_err);
            continue;
        }

        if ($http_code === 200 && $response) {
            $data = json_decode($response, true);
            $raw_json = $data['choices'][0]['message']['content'] ?? '';
            $parsed = parse_ai_json_response($raw_json);
            if ($parsed) {
                return ['success' => true, 'data' => $parsed, 'model_used' => $model];
            }
        } else {
            error_log("OpenAI API HTTP {$http_code} ({$model}): " . $response);
        }
    }

    return ['success' => false, 'message' => 'Unable to connect to AI vision service. Please check AI settings.'];
}

/**
 * Call Google Gemini Vision API
 */
function call_gemini_vision_api(?string $base64_image): array {
    $api_url = rtrim(get_setting('gemini_api_url', 'https://generativelanguage.googleapis.com/v1beta'), '/');
    $api_key = get_setting('gemini_api_key', '');
    $primary_model = get_setting('gemini_model', 'gemini-3.5-flash-lite');
    $fallbacks_str = get_setting('gemini_fallback_models', '');

    $models_to_try = array_filter(array_map('trim', array_merge([$primary_model], explode(',', $fallbacks_str))));

    if (empty($api_key)) {
        return ['success' => false, 'message' => 'Gemini API key not configured in Admin Settings.'];
    }

    $system_prompt = get_ai_system_prompt();
    $parts = [['text' => $system_prompt]];

    if ($base64_image) {
        $mime_type = 'image/jpeg';
        $pure_base64 = $base64_image;
        if (preg_match('/^data:(image\/\w+);base64,(.*)$/', $base64_image, $matches)) {
            $mime_type = $matches[1];
            $pure_base64 = $matches[2];
        }

        $parts[] = [
            'inlineData' => [
                'mimeType' => $mime_type,
                'data' => $pure_base64
            ]
        ];
    }

    foreach ($models_to_try as $model) {
        $endpoint = "{$api_url}/models/{$model}:generateContent?key={$api_key}";
        $payload = [
            'contents' => [
                ['parts' => $parts]
            ],
            'generationConfig' => [
                'temperature' => 0.2,
                'responseMimeType' => 'application/json'
            ]
        ];

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 45,
            CURLOPT_SSL_VERIFYPEER => true
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_err = curl_error($ch);
        curl_close($ch);

        if ($curl_err) {
            error_log("Gemini cURL Error ({$model}): " . $curl_err);
            continue;
        }

        if ($http_code === 200 && $response) {
            $data = json_decode($response, true);
            $raw_json = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
            $parsed = parse_ai_json_response($raw_json);
            if ($parsed) {
                return ['success' => true, 'data' => $parsed, 'model_used' => $model];
            }
        } else {
            error_log("Gemini API HTTP {$http_code} ({$model}): " . $response);
        }
    }

    return ['success' => false, 'message' => 'Unable to connect to Gemini vision service. Please check AI settings.'];
}

/**
 * Test OpenAI Connection
 */
function test_openai_connection(string $url, string $key, string $model): array {
    $endpoint = rtrim($url, '/') . '/chat/completions';
    $payload = [
        'model' => $model,
        'messages' => [['role' => 'user', 'content' => 'Hello, reply with JSON: {"status": "ok"}']],
        'temperature' => 0.1
    ];

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $key
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) {
        return ['success' => false, 'message' => "Network Connection Error: {$err}"];
    }

    if ($http_code === 200) {
        return ['success' => true, 'provider' => 'Agnes AI / OpenAI', 'model' => $model, 'message' => 'Connection successful!'];
    }

    return ['success' => false, 'message' => ai_http_error_message($http_code, (string)$response)];
}

/**
 * Test Gemini Connection
 */
function test_gemini_connection(string $url, string $key, string $model): array {
    $endpoint = rtrim($url, '/') . "/models/{$model}:generateContent?key={$key}";
    $payload = [
        'contents' => [
            ['parts' => [['text' => 'Hello, reply with JSON: {"status": "ok"}']]]
        ]
    ];

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) {
        return ['success' => false, 'message' => "Network Connection Error: {$err}"];
    }

    if ($http_code === 200) {
        return ['success' => true, 'provider' => 'Google Gemini', 'model' => $model, 'message' => 'Connection successful!'];
    }

    return ['success' => false, 'message' => ai_http_error_message($http_code, (string)$response)];
}

/**
 * Turn an API error body into a short message without echoing secrets.
 */
function ai_http_error_message(int $http_code, string $response): string {
    $data = json_decode($response, true);
    $detail = '';
    if (is_array($data)) {
        $detail = (string)($data['error']['message'] ?? $data['message'] ?? '');
    }
    $detail = trim(preg_replace('/\s+/', ' ', $detail));
    if (strlen($detail) > 180) {
        $detail = substr($detail, 0, 180) . '...';
    }
    if ($detail !== '') {
        return "API returned HTTP code {$http_code}. {$detail}";
    }
    return "API returned HTTP code {$http_code}. Check the base URL and model name.";
}

/**
 * Safely extract and parse JSON from AI string response
 */
function parse_ai_json_response(string $raw_text): ?array {
    $raw_text = trim($raw_text);
    // Strip markdown code fences if present
    $raw_text = preg_replace('/^```(?:json)?\s*/i', '', $raw_text);
    $raw_text = preg_replace('/\s*```$/i', '', $raw_text);

    $json = json_decode($raw_text, true);
    if (is_array($json)) {
        $confidence = isset($json['confidence']) ? floatval($json['confidence']) : 0;
        if ($confidence > 1) {
            $confidence = $confidence / 100;
        }

        return [
            'sku' => $json['sku'] ?? null,
            'product_name' => $json['product_name'] ?? null,
            'confidence' => $confidence,
            'reason' => $json['reason'] ?? ''
        ];
    }
    return null;
}
