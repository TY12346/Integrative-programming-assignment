<?php
/**
 * FoodLink - Module 4 Delivery & Impact Tracking
 * Author : KHOO SHENG HAO
 * Purpose: Demo REST client for a HMAC-signed delivery status update.
 * Usage:
 *   set FOODLINK_DELIVERY_API_TOKEN=foodlink-delivery-volunteer-demo-token
 *   set DELIVERY_API_HMAC_SECRET=your-secret
 *   php scripts/delivery_api_client.php 1 PICKED_UP "Collected from donor"
 */

$deliveryId = (int) ($argv[1] ?? 0);
$status = (string) ($argv[2] ?? 'PICKED_UP');
$remarks = (string) ($argv[3] ?? 'Signed API demo update');
$baseUrl = rtrim(getenv('FOODLINK_API_BASE_URL') ?: 'http://localhost:8000', '/');
$token = getenv('FOODLINK_DELIVERY_API_TOKEN') ?: '';
$secret = getenv('DELIVERY_API_HMAC_SECRET') ?: '';

if ($deliveryId < 1 || $token === '' || $secret === '') {
    fwrite(STDERR, "Provide a delivery ID and set FOODLINK_DELIVERY_API_TOKEN and DELIVERY_API_HMAC_SECRET.\n");
    exit(1);
}

$path = "/api/v1/deliveries/{$deliveryId}/status";
$body = json_encode(['delivery_status' => $status, 'remarks' => $remarks], JSON_UNESCAPED_SLASHES);
$timestamp = (string) time();
$requestId = bin2hex(random_bytes(16));
$canonical = implode("\n", ['PATCH', $path, $timestamp, $requestId, hash('sha256', $body)]);
$signature = hash_hmac('sha256', $canonical, $secret);

$ch = curl_init($baseUrl.$path);
curl_setopt_array($ch, [
    CURLOPT_CUSTOMREQUEST => 'PATCH',
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
