<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\ServiceProviderProfileController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ServiceSlotController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/services', [ServiceController::class, 'index']);


Route::get('/service-providers/{user_id}/profile', [ServiceProviderProfileController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::get('/categories/{id}', [CategoryController::class, 'show']);
    Route::get('/my-services', [ServiceController::class, 'myServices']);
    Route::post('/services', [ServiceController::class, 'store']);

    
    Route::post('/services/{id}/book', [BookingController::class, 'book']);         
    Route::post('/services/{id}/bookings', [BookingController::class, 'store']);    
    Route::get('/my-bookings', [BookingController::class, 'myBookings']);              
    Route::get('/services/{id}/bookings', [BookingController::class, 'serviceBookings']); 
    Route::put('/bookings/{id}/status', [BookingController::class, 'updateStatus']);   
    Route::delete('/bookings/{id}', [BookingController::class, 'destroy']);            

   
    Route::get('/services/{id}/slots', [ServiceSlotController::class, 'availableSlots']); 
});

Route::middleware(['auth:sanctum', 'checkrole:admin'])->group(function () {
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{id}', [CategoryController::class, 'update']);
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);
    Route::post('/services/{id}/approve', [ServiceController::class, 'approve']);
});

Route::middleware(['auth:sanctum', 'checkrole:service_provider'])->group(function () {
    Route::get('/my-profile', [ServiceProviderProfileController::class, 'myProfile']);
    Route::post('/profile', [ServiceProviderProfileController::class, 'upsert']);

    
    Route::post('/services/{id}/slots', [ServiceSlotController::class, 'store']);     
    Route::put('/slots/{id}', [ServiceSlotController::class, 'update']);            
    Route::delete('/slots/{id}', [ServiceSlotController::class, 'destroy']);          
});

Route::middleware(['auth:sanctum', 'checkrole:user'])->group(function () {
    
});
