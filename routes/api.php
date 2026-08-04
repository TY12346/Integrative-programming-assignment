<?php
use Illuminate\Support\Facades\Route; use App\Http\Controllers\ApiController;
Route::get('/partners/{id}/status',[ApiController::class,'partnerStatus']);
Route::get('/donations/available',[ApiController::class,'availableDonations']);
Route::get('/requests/{id}/reservations',[ApiController::class,'requestReservations']);
Route::get('/deliveries/{id}/status',[ApiController::class,'deliveryStatus']);

/*
|--------------------------------------------------------------------------
| Module 3.3 Food Request Management - REST web service
| Author: NG JIA QIN
|--------------------------------------------------------------------------
| Versioned under /api/v1 so the contract can evolve without breaking the
| clients that other modules already use.
|
| Security: every route requires a bearer token (api.token middleware) and is
| rate limited to 60 calls per minute per client, which blunts both brute force
| token guessing and scraping of the donation board.
*/
Route::prefix('v1')
    ->middleware(['api.token', 'throttle:60,1'])
    ->group(function () {
        Route::get('/requests', [\App\Http\Controllers\Api\FoodRequestApiController::class, 'index']);
        Route::post('/requests', [\App\Http\Controllers\Api\FoodRequestApiController::class, 'store']);
        Route::get('/requests/{foodRequest}', [\App\Http\Controllers\Api\FoodRequestApiController::class, 'show']);
        Route::patch('/requests/{foodRequest}', [\App\Http\Controllers\Api\FoodRequestApiController::class, 'update']);
        Route::post('/requests/{foodRequest}/cancel', [\App\Http\Controllers\Api\FoodRequestApiController::class, 'cancel']);
        Route::get('/requests/{foodRequest}/status', [\App\Http\Controllers\Api\FoodRequestApiController::class, 'status']);
        Route::get('/requests/{foodRequest}/reservations', [\App\Http\Controllers\Api\FoodRequestApiController::class, 'reservations']);
        Route::post('/requests/{foodRequest}/reservations', [\App\Http\Controllers\Api\FoodRequestApiController::class, 'reserve']);
        Route::get('/donations', [\App\Http\Controllers\Api\FoodRequestApiController::class, 'donations']);
    });
