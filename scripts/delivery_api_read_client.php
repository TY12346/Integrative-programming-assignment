<?php
/**
 * FoodLink - Module 4 Delivery & Impact Tracking
 * Author : KHOO SHENG HAO
 * Purpose: JSON web-service client modelled after the Practical 5
 *          file_get_contents() + json_decode() example.
 *
 * Usage:
 *   set FOODLINK_DELIVERY_API_TOKEN=foodlink-delivery-volunteer-demo-token
 *   php scripts/delivery_api_read_client.php
 */

$baseUrl = rtrim(getenv('FOODLINK_API_BASE_URL') ?: 'http://localhost:8000', '/');
$token = getenv('FOODLINK_DELIVERY_API_TOKEN') ?: '';
$url = $baseUrl.'/api/v1/deliveries';

if ($token === '') {
    fwrite(STDERR, "Set FOODLINK_DELIVERY_API_TOKEN before running this client.\n");
    exit(1);
}

// Same idea as the practical: make an HTTP GET request and receive JSON.
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => "Accept: application/json\r\nAuthorization: Bearer {$token}\r\n",
        'ignore_errors' => true,
        'timeout' => 5,
    ],
]);

$response = file_get_contents($url, false, $context);

if ($response === false) {
    fwrite(STDERR, "Error: unable to fetch delivery data.\n");
    exit(1);
}

// Same concept as Practical 5: JSON -> PHP associative array.
$payload = json_decode($response, true);

if (! is_array($payload) || ! isset($payload['data']) || ! is_array($payload['data'])) {
    fwrite(STDERR, "Error: invalid JSON response.\n{$response}\n");
    exit(1);
}

printf("%-5s %-14s %-25s %-20s\n", 'ID', 'STATUS', 'VOLUNTEER', 'ASSIGNED');
printf("%'-70s\n", '');

foreach ($payload['data'] as $delivery) {
    printf(
        "%-5s %-14s %-25s %-20s\n",
        $delivery['delivery_id'] ?? '-',
        $delivery['delivery_status'] ?? '-',
        $delivery['volunteer_name'] ?? '-',
        $delivery['assigned_at'] ?? '-'
    );
}
