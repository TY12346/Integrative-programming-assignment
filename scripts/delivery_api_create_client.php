<?php
/**
 * FoodLink - Module 4 Delivery & Impact Tracking
 * Author : KHOO SHENG HAO
 * Purpose: Demo REST client for a HMAC-signed POST delivery request.
 *
 * Usage:
 *   set FOODLINK_DELIVERY_API_TOKEN=foodlink-delivery-volunteer-demo-token
 *   set DELIVERY_API_HMAC_SECRET=your-secret
 *   php scripts/delivery_api_create_client.php 2 "Handle with care"
 */

$reservationId = (int) ($argv[1] ?? 0);
$notes = (string) ($argv[2] ?? 'Created through the Delivery REST API');
$baseUrl = rtrim(getenv('FOODLINK_API_BASE_URL') ?: 'http://localhost:8000', '/');
$token = getenv('FOODLINK_DELIVERY_API_TOKEN') ?: '';
$secret = getenv('DELIVERY_API_HMAC_SECRET') ?: '';

if ($reservationId < 1 || $token === '' || $secret === '') {
    fwrite(STDERR, "Provide a reservation ID and set FOODLINK_DELIVERY_API_TOKEN and DELIVERY_API_HMAC_SECRET.\n");
    exit(1);
}

$path = '/api/v1/deliveries';
$body = json_encode([
    'reservation_id' => $reservationId,
    'delivery_notes' => $notes,
], JSON_UNESCAPED_SLASHES);
$timestamp = (string) time();
$requestId = bin2hex(random_bytes(16));
$canonical = implode("\n", ['POST', $path, $timestamp, $requestId, hash('sha256', $body)]);
$signature = hash_hmac('sha256', $canonical, $secret);

$ch = curl_init($baseUrl.$path);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $body,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Bearer '.$token,
        'X-FoodLink-Request-Id: '.$requestId,
        'X-FoodLink-Timestamp: '.$timestamp,
        'X-FoodLink-Signature: '.$signature,
    ],
    CURLOPT_RETURNTRANSFER => true,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

if ($response === false) {
    fwrite(STDERR, 'cURL error: '.curl_error($ch)."\n");
    exit(1);
}

curl_close($ch);
echo "HTTP {$httpCode}\n{$response}\n";
