<?php

$deliveryId = $argv[1] ?? null;

if (!$deliveryId) {
    die("Usage: php scripts/replay_test.php <delivery_id>\n");
}

$baseUrl = 'http://127.0.0.1:8000';

$token = getenv('FOODLINK_DELIVERY_API_TOKEN');
$secret = getenv('DELIVERY_API_HMAC_SECRET');

if (!$token || !$secret) {
    die(
        "Set FOODLINK_DELIVERY_API_TOKEN and DELIVERY_API_HMAC_SECRET first.\n"
    );
}

/*
 * IMPORTANT:
 * Generate these only ONCE.
 * Both requests below reuse exactly the same values.
 */
$requestId = 'REPLAY-TEST-0001';
$timestamp = (string) time();

$path = "/api/v1/deliveries/{$deliveryId}/status";

$body = json_encode([
    'delivery_status' => 'PICKED_UP',
    'remarks' => 'Replay attack test'
], JSON_UNESCAPED_SLASHES);

$canonical = implode("\n", [
    'PATCH',
    $path,
    $timestamp,
    $requestId,
    hash('sha256', $body),
]);

$signature = hash_hmac(
    'sha256',
    $canonical,
    $secret
);

function sendRequest(
    string $url,
    string $token,
    string $requestId,
    string $timestamp,
    string $signature,
    string $body
): string {

    $context = stream_context_create([
        'http' => [
            'method' => 'PATCH',
            'header' =>
                "Accept: application/json\r\n" .
                "Content-Type: application/json\r\n" .
                "Authorization: Bearer {$token}\r\n" .
                "X-FoodLink-Request-Id: {$requestId}\r\n" .
                "X-FoodLink-Timestamp: {$timestamp}\r\n" .
                "X-FoodLink-Signature: {$signature}\r\n",
            'content' => $body,
            'ignore_errors' => true,
        ],
    ]);

    return file_get_contents($url, false, $context);
}

$url = $baseUrl . $path;

echo "===== FIRST REQUEST =====\n";

$response1 = sendRequest(
    $url,
    $token,
    $requestId,
    $timestamp,
    $signature,
    $body
);

echo $response1 . "\n\n";

echo "===== SAME REQUEST REPLAYED =====\n";

$response2 = sendRequest(
    $url,
    $token,
    $requestId,
    $timestamp,
    $signature,
    $body
);

echo $response2 . "\n";