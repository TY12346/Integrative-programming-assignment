<?php



/* Module 3.1 User Management - Ong Tin Yin */
use Illuminate\Support\Facades\Route; use App\Http\Controllers\{AuthController,DashboardController,ProfileController,AdminController,DonationController,RequestController,DeliveryController};
Route::get('/', fn()=>redirect('/dashboard'));
Route::get('/login',[AuthController::class,'loginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:20,1');
Route::get('/register',[AuthController::class,'registerForm']);
Route::post('/register',[AuthController::class,'register']);
Route::post('/logout',[AuthController::class,'logout']);
Route::middleware('auth')->group(function(){ Route::get('/dashboard',DashboardController::class);
Route::get('/profile',[ProfileController::class,'show']);
Route::post('/profile',[ProfileController::class,'update']);
Route::delete('/profile',[ProfileController::class,'destroy']);
Route::post('/profile/document',[ProfileController::class,'uploadDocument']);
Route::get('/donations/available',[DonationController::class,'available'])->middleware('verified.role:FOOD_DONOR,CHARITY,VOLUNTEER,ADMIN');
Route::middleware(
    'verified.role:FOOD_DONOR,CHARITY,VOLUNTEER,ADMIN'
)->group(function () {
    Route::get('/donations/{donation}', [
        DonationController::class,
        'show'
    ])
        ->whereNumber('donation')
        ->name('donations.show');

    Route::get('/donations/photos/{photo}/file', [
        DonationController::class,
        'servePhoto'
    ])->whereNumber('photo');
});
Route::middleware('verified.role:ADMIN')->group(function(){ Route::get('/admin/users',[AdminController::class,'users']);
Route::post('/admin/users',[AdminController::class,'storeAdmin']);
Route::patch('/admin/users/{user}',[AdminController::class,'updateStatus']);
Route::get('/admin/verifications',[AdminController::class,'verifications']);
Route::get('/admin/verifications/{profile}',[AdminController::class,'verificationHistory']);
Route::post('/admin/verifications/{profile}',[
    AdminController::class,
    'review'
]);

Route::get('/admin/verification-documents/{document}', [
    AdminController::class,
    'viewVerificationDocument'
])->name('admin.verification-documents.view');

});

Route::middleware('role:FOOD_DONOR')->group(function () {
    Route::get('/donations/create', [
        DonationController::class,
        'create'
    ])->name('donations.create');

    Route::post('/donations', [
        DonationController::class,
        'store'
    ])->name('donations.store');
});

Route::middleware('verified.role:FOOD_DONOR')->group(function(){ 
    Route::resource(
        'donations',
        DonationController::class
    )->except([
        'destroy',
        'create',
        'store',
        'show'
    ]);
    Route::post('/donations/{donation}/cancel',[DonationController::class,'cancel']);
    Route::post('/donations/{donation}/photos',[DonationController::class,'storePhotos']);
    Route::delete('/donations/{donation}/photos/{photo}',[DonationController::class,'destroyPhoto']);
    
});

  
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
 
 /*
|--------------------------------------------------------------------------
| Module 3.4 Delivery & Impact Tracking - KHOO SHENG HAO
|--------------------------------------------------------------------------
*/
Route::middleware('verified.role:VOLUNTEER,ADMIN')->group(function () {

    // View all visible delivery tasks
    Route::get('/deliveries',[DeliveryController::class, 'index'])->name('deliveries.index');

    // Show create delivery form
    Route::get('/deliveries/create',[DeliveryController::class, 'create'])->name('deliveries.create');

    // Store a new delivery task
    Route::post('/deliveries',[DeliveryController::class, 'store'])->name('deliveries.store');

    // Create/claim a task directly from a reservation
    Route::post('/reservations/{reservation}/delivery',[DeliveryController::class, 'createFromReservation'])->name('deliveries.createFromReservation');

    // View one delivery task
    Route::get('/deliveries/{delivery}',[DeliveryController::class, 'show'])->name('deliveries.show');

    // Show delivery status update form
    Route::get('/deliveries/{delivery}/edit',[DeliveryController::class, 'edit'])->name('deliveries.edit');

    // Update delivery status
    Route::patch('/deliveries/{delivery}',[DeliveryController::class, 'update'])->name('deliveries.update');
});

});
 
