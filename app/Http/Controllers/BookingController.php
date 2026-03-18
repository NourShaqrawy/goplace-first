<?php

namespace App\Http\Controllers;

use App\Models\Balance;
use App\Models\Booking;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | 1) إنشاء طلب حجز (بدون تحديد وقت)
    |--------------------------------------------------------------------------
    */
    public function store(Request $request, $service_id)
    {
        $user = Auth::user();

        // جلب الخدمة
        $service = Service::where('id', $service_id)
            ->where('is_approved', true)
            ->first();

        if (!$service) {
            return response()->json(['message' => 'الخدمة غير موجودة أو غير معتمدة'], 404);
        }

        // منع مقدم الخدمة من حجز خدمته
        if ($service->provider_id == $user->id) {
            return response()->json(['message' => 'لا يمكنك حجز خدمتك الخاصة'], 403);
        }

        // إنشاء الحجز بدون وقت
        $booking = Booking::create([
            'user_id'      => $user->id,
            'service_id'   => $service->id,
            'status'       => 'pending',
            'scheduled_at' => null,
            'amount_paid'  => null,
        ]);

        return response()->json([
            'message' => 'تم إرسال طلب الحجز بنجاح، بانتظار تحديد التوقيت من مقدم الخدمة',
            'data'    => $booking,
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | 2) مقدم الخدمة يحدد وقت الحجز
    |--------------------------------------------------------------------------
    */
    public function schedule(Request $request, $id)
    {
        $request->validate([
            'scheduled_at' => 'required|date|after:now',
        ]);

        $booking = Booking::findOrFail($id);

        // تحقق أن مقدم الخدمة هو صاحب الخدمة
        if ($booking->service->provider_id !== Auth::id()) {
            return response()->json(['message' => 'غير مسموح'], 403);
        }

        $booking->update([
            'scheduled_at' => $request->scheduled_at,
            'status'       => 'scheduled',
        ]);

        return response()->json([
            'message' => 'تم تحديد التوقيت بنجاح',
            'data'    => $booking,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | 3) المستخدم يؤكد الحجز (مع الدفع)
    |--------------------------------------------------------------------------
    */
    public function confirm($id)
    {
        $booking = Booking::findOrFail($id);

        if ($booking->user_id !== Auth::id()) {
            return response()->json(['message' => 'غير مسموح'], 403);
        }

        if (!$booking->scheduled_at) {
            return response()->json(['message' => 'لا يمكن تأكيد الحجز قبل تحديد التوقيت'], 400);
        }

        $service = $booking->service;

        // رصيد المستخدم
        $balance = Balance::firstOrCreate(['user_id' => Auth::id()]);
        $providerBalance = Balance::firstOrCreate(['user_id' => $service->provider_id]);

        if ($balance->current_balance < $service->book_price) {
            return response()->json(['message' => 'الرصيد غير كافٍ'], 400);
        }

        DB::beginTransaction();

        try {
            // خصم من المستخدم
            $balance->current_balance -= $service->book_price;
            $balance->save();

            // إضافة للمزود
            $providerBalance->current_balance += $service->book_price;
            $providerBalance->save();

            // تحديث حالة الحجز
            $booking->update([
                'status'      => 'confirmed',
                'amount_paid' => $service->book_price,
            ]);

            DB::commit();

            return response()->json(['message' => 'تم تأكيد الحجز بنجاح']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'حدث خطأ أثناء الدفع'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | 4) إلغاء الحجز من المستخدم
    |--------------------------------------------------------------------------
    */
    public function cancel($id)
    {
        $booking = Booking::findOrFail($id);

        if ($booking->user_id !== Auth::id()) {
            return response()->json(['message' => 'غير مسموح'], 403);
        }

        $booking->update(['status' => 'cancelled']);

        return response()->json(['message' => 'تم إلغاء الحجز']);
    }

    /*
    |--------------------------------------------------------------------------
    | 5) إكمال الحجز (بعد تنفيذ الخدمة)
    |--------------------------------------------------------------------------
    */
    public function complete($id)
    {
        $booking = Booking::findOrFail($id);

        // تحقق أن مقدم الخدمة هو صاحب الخدمة
        if ($booking->service->provider_id !== Auth::id()) {
            return response()->json(['message' => 'غير مسموح'], 403);
        }

        // يجب أن يكون الحجز مؤكد قبل إكماله
        if ($booking->status !== 'confirmed') {
            return response()->json(['message' => 'لا يمكن إكمال حجز غير مؤكد'], 400);
        }

        $booking->update(['status' => 'completed']);

        return response()->json(['message' => 'تم إنهاء الحجز بنجاح']);
    }

    /*
    |--------------------------------------------------------------------------
    | 6) حجوزات المستخدم
    |--------------------------------------------------------------------------
    */
    public function myBookings()
    {
        $bookings = Booking::where('user_id', Auth::id())
            ->with('service')
            ->get();

        return response()->json($bookings);
    }

    /*
    |--------------------------------------------------------------------------
    | 7) حجوزات مقدم الخدمة
    |--------------------------------------------------------------------------
    */
    public function providerBookings()
    {
        $services = Service::where('provider_id', Auth::id())->pluck('id');

        $bookings = Booking::whereIn('service_id', $services)
            ->with('user')
            ->get();

        return response()->json($bookings);
    }
}
