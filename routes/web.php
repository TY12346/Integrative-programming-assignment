<?php
use Illuminate\Support\Facades\Route; use App\Http\Controllers\{AuthController,DashboardController,ProfileController,AdminController,DonationController,RequestController,DeliveryController};
Route::get('/', fn()=>redirect('/dashboard'));
Route::get('/login',[AuthController::class,'loginForm'])->name('login'); Route::post('/login',[AuthController::class,'login']); Route::get('/register',[AuthController::class,'registerForm']); Route::post('/register',[AuthController::class,'register']); Route::post('/logout',[AuthController::class,'logout']);
Route::middleware('auth')->group(function(){ Route::get('/dashboard',DashboardController::class); Route::get('/profile',[ProfileController::class,'show']); Route::post('/profile',[ProfileController::class,'update']); Route::delete('/profile',[ProfileController::class,'destroy']); Route::post('/profile/document',[ProfileController::class,'uploadDocument']); Route::get('/donations/available',[DonationController::class,'available'])->middleware('verified.role:FOOD_DONOR,CHARITY,VOLUNTEER,ADMIN');
 Route::middleware('verified.role:ADMIN')->group(function(){ Route::get('/admin/users',[AdminController::class,'users']); Route::post('/admin/users',[AdminController::class,'storeAdmin']); Route::patch('/admin/users/{user}',[AdminController::class,'updateStatus']); Route::get('/admin/verifications',[AdminController::class,'verifications']); Route::get('/admin/verifications/{profile}',[AdminController::class,'verificationHistory']); Route::post('/admin/verifications/{profile}',[AdminController::class,'review']); });
 Route::middleware('verified.role:FOOD_DONOR')->group(function(){ Route::resource('donations',DonationController::class)->except(['show','destroy']); Route::post('/donations/{donation}/cancel',[DonationController::class,'cancel']); });
  
 /* Module 3.3 Food Request Management - NG JIA QIN */
 Route::middleware('verified.role:CHARITY')->group(function(){
     // Declared before the resource routes so that /requests/donations is not
     // mistaken for /requests/{foodRequest}.
     Route::get('/requests/donations',[RequestController::class,'donations'])->name('requests.donations');
     Route::resource('requests',RequestController::class)->parameters(['requests'=>'foodRequest'])->except(['destroy']);
     Route::post('/requests/{foodRequest}/cancel',[RequestController::class,'cancel'])->name('requests.cancel');
     Route::post('/requests/{foodRequest}/reservations',[RequestController::class,'reserve'])->name('requests.reserve');
     Route::delete('/requests/{foodRequest}/reservations/{reservation}',[RequestController::class,'releaseReservation'])->name('requests.reservations.release');
 });
 
 Route::middleware('verified.role:VOLUNTEER,ADMIN')->group(function(){ Route::get('/deliveries',[DeliveryController::class,'index']); Route::post('/reservations/{reservation}/delivery',[DeliveryController::class,'createFromReservation']); Route::get('/deliveries/{delivery}/edit',[DeliveryController::class,'edit']); Route::patch('/deliveries/{delivery}',[DeliveryController::class,'update']); }); });
 
 