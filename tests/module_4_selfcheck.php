<?php
/**
 * FoodLink - Module 4 Delivery & Impact Tracking
 * Author : KHOO SHENG HAO
 * Purpose: Lightweight source self-check that does not require Laravel vendor files.
 */

$root = dirname(__DIR__);
$routes = file_get_contents($root.'/routes/api.php');
$apiController = file_get_contents($root.'/app/Http/Controllers/Api/DeliveryApiController.php');
$readClient = file_get_contents($root.'/scripts/delivery_api_read_client.php');

$checks = [
    'Observer implementation exists' => str_contains(file_get_contents($root.'/app/Observers/DeliveryImpactObserver.php'), 'class DeliveryImpactObserver'),
    'Observer reacts to status changes' => str_contains(file_get_contents($root.'/app/Observers/DeliveryImpactObserver.php'), "wasChanged('delivery_status')"),
    'Observer records impact idempotently' => str_contains(file_get_contents($root.'/app/Observers/DeliveryImpactObserver.php'), 'firstOrCreate'),
    'REST GET collection route exists' => str_contains($routes, "Route::get('/deliveries'"),
    'REST GET single route exists' => str_contains($routes, "Route::get('/deliveries/{delivery}'"),
    'REST POST create route exists' => str_contains($routes, "Route::post('/deliveries'"),
    'REST PATCH status route exists' => str_contains($routes, "Route::patch('/deliveries/{delivery}/status'"),
    'Delivery REST API requires bearer token' => str_contains($routes, "'api.token'"),
    'State-changing REST routes require HMAC' => str_contains($routes, "Route::middleware('delivery.hmac')"),
    'API update attributes authenticated user' => str_contains($apiController, '$request->user(), $data'),
    'HMAC uses hash_hmac' => str_contains(file_get_contents($root.'/app/Http/Middleware/VerifyDeliveryHmac.php'), 'hash_hmac'),
    'HMAC uses constant-time hash_equals' => str_contains(file_get_contents($root.'/app/Http/Middleware/VerifyDeliveryHmac.php'), 'hash_equals'),
    'Replay request IDs are persisted' => str_contains(file_get_contents($root.'/database/migrations/2026_09_10_000000_extend_delivery_impact_module.php'), "string('request_id', 100)->unique()"),
    'Timestamp window is checked' => str_contains(file_get_contents($root.'/app/Http/Middleware/VerifyDeliveryHmac.php'), 'hmac_window_seconds'),
    'Delivery notes are sanitised before storage' => str_contains(file_get_contents($root.'/app/Services/DeliveryNoteSanitizer.php'), 'strip_tags'),
    'Delivery notes use escaped Blade output' => str_contains(file_get_contents($root.'/resources/views/deliveries/show.blade.php'), '{{ $delivery->delivery_notes'),
    'Practical-style client uses file_get_contents' => str_contains($readClient, 'file_get_contents'),
    'Practical-style client decodes JSON' => str_contains($readClient, 'json_decode'),
];

$failed = 0;
foreach ($checks as $name => $ok) {
    echo ($ok ? '[PASS] ' : '[FAIL] ').$name.PHP_EOL;
    $failed += $ok ? 0 : 1;
}

exit($failed === 0 ? 0 : 1);
