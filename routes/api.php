<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\ServiceProviderProfileController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ServiceSlotController;
use App\Http\Controllers\BalanceController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\TopupController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

//روابط عامة
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/services-accept', [ServiceController::class, 'indexApproved']);
Route::get('/services/{id}', [ServiceController::class, 'show']);
Route::get('/service-providers/{user_id}/profile', [ServiceProviderProfileController::class, 'show']);
Route::get('/services/{id}/comments', [CommentController::class, 'index']);
Route::get('/services/{id}/rating-stats', [RatingController::class, 'stats']);

//روابط لتشغيلهبحب تسجيل دخول
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
    Route::get('/my-balance', [BalanceController::class, 'myBalance']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::get('/balances/{userId}', [BalanceController::class, 'show']);
    Route::delete('/services/{id}', [ServiceController::class, 'destroy']);
});

//روابط حاصة بالادمن
Route::middleware(['auth:sanctum', 'checkrole:admin'])->group(function () {
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{id}', [CategoryController::class, 'update']);
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);
    Route::post('/services/{id}/approve', [ServiceController::class, 'approve']);
    Route::get('/topups', [TopupController::class, 'index']);
    Route::get('/services-notaccept', [ServiceController::class, 'indexNotApproved']);
    Route::put('/topups/{id}/approve', [TopupController::class, 'approve']);
    Route::put('/topups/{id}/reject', [TopupController::class, 'reject']);
    Route::get('/balances', [BalanceController::class, 'index']);
    Route::post('/balances/{userId}', [BalanceController::class, 'saveBalance']);
    Route::delete('/balances/{userId}', [BalanceController::class, 'destroy']);
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
    Route::delete('/comments/{id}', [CommentController::class, 'destroy']);
});
//روابط حاصة بمقدم الخدمة
Route::middleware(['auth:sanctum', 'checkrole:service_provider'])->group(function () {
    Route::get('/my-profile', [ServiceProviderProfileController::class, 'myProfile']);
    Route::post('/profile', [ServiceProviderProfileController::class, 'upsert']);
    Route::post('/services/{id}/slots', [ServiceSlotController::class, 'store']);
    Route::put('/slots/{id}', [ServiceSlotController::class, 'update']);
    Route::delete('/slots/{id}', [ServiceSlotController::class, 'destroy']);
    Route::put('/services/{id}', [ServiceController::class, 'update']);
    // Route::get('/services/{id}/bookings', [BookingController::class, 'serviceBookings']);
    // Route::put('/bookings/{id}/status', [BookingController::class, 'updateStatus']);
    
    Route::get('/provider/bookings', [BookingController::class, 'providerBookings']);

    // تحديد وقت الحجز
    Route::post('/bookings/{id}/schedule', [BookingController::class, 'schedule']);

    });

//روابط حاصة بالمستخدم العادي
Route::middleware(['auth:sanctum', 'checkrole:user'])->group(function () {
    Route::post('/topups', [TopupController::class, 'store']);

    // إنشاء طلب حجز
    Route::post('/services/{id}/book', [BookingController::class, 'store']);

    // حجوزاتي
    Route::get('/my-bookings', [BookingController::class, 'myBookings']);

    // تأكيد الحجز (مع الدفع)
    Route::post('/bookings/{id}/confirm', [BookingController::class, 'confirm']);

    // إلغاء الحجز
    Route::post('/bookings/{id}/cancel', [BookingController::class, 'cancel']);

    Route::post('/services/{id}/comment', [CommentController::class, 'store']);
    Route::put('/comments/{id}', [CommentController::class, 'update']);
    Route::delete('/comments/{id}', [CommentController::class, 'destroy']);

    // التقييمات
    Route::post('/services/{id}/rate', [RatingController::class, 'store']);
    Route::put('/ratings/{id}', [RatingController::class, 'update']);
});
