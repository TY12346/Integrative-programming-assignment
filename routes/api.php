<?php
use Illuminate\Support\Facades\Route; use App\Http\Controllers\ApiController;
Route::get('/partners/{id}/status',[ApiController::class,'partnerStatus']);
Route::get('/donations/available',[ApiController::class,'availableDonations']);
Route::get('/requests/{id}/reservations',[ApiController::class,'requestReservations']);
Route::get('/deliveries/{id}/status',[ApiController::class,'deliveryStatus']);
