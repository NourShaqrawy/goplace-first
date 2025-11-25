<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\ServiceProviderProfileController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ServiceSlotController;
use App\Http\Controllers\BalanceController;
use App\Http\Controllers\TopupController;
use Illuminate\Support\Facades\Route;

// 🔹 روابط عامة (بدون تسجيل دخول)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/services', [ServiceController::class, 'index']);
Route::get('/services/{id}', [ServiceController::class, 'show']);
Route::get('/service-providers/{user_id}/profile', [ServiceProviderProfileController::class, 'show']);

// 🔹 روابط مشتركة للمستخدم المسجّل
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

// 🔹 روابط خاصة بالأدمن
Route::middleware(['auth:sanctum', 'checkrole:admin'])->group(function () {
    // إدارة التصنيفات والخدمات
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{id}', [CategoryController::class, 'update']);
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);
    Route::post('/services/{id}/approve', [ServiceController::class, 'approve']);

    // إدارة طلبات الشحن (Topups)
    Route::get('/topups', [TopupController::class, 'index']); 
    Route::put('/topups/{id}/approve', [TopupController::class, 'approve']); 
    Route::put('/topups/{id}/reject', [TopupController::class, 'reject']);   

    // إدارة الأرصدة (Balances)
    Route::post('/balances', [BalanceController::class, 'store']);   
    Route::put('/balances/{userId}', [BalanceController::class, 'update']); 
    Route::delete('/balances/{userId}', [BalanceController::class, 'destroy']); 
});

// 🔹 روابط خاصة بمقدم الخدمة
Route::middleware(['auth:sanctum', 'checkrole:service_provider'])->group(function () {
    Route::get('/my-profile', [ServiceProviderProfileController::class, 'myProfile']);
    Route::post('/profile', [ServiceProviderProfileController::class, 'upsert']);

    Route::post('/services/{id}/slots', [ServiceSlotController::class, 'store']);     
    Route::put('/slots/{id}', [ServiceSlotController::class, 'update']);            
    Route::delete('/slots/{id}', [ServiceSlotController::class, 'destroy']);          
});

// 🔹 روابط خاصة بالمستخدم العادي
Route::middleware(['auth:sanctum', 'checkrole:user'])->group(function () {
    // المستخدم يرفع طلب شحن رصيد
    Route::post('/topups', [TopupController::class, 'store']);
    
    // المستخدم يشاهد رصيده الحالي
    Route::get('/balances/{userId}', [BalanceController::class, 'show']);
});
