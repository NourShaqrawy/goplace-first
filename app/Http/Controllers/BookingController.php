<?php

namespace App\Http\Controllers;

use App\Models\Balance;
use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use function Termwind\parse;

class BookingController extends Controller
{
    public function book(Request $request, $service_id)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // التحقق من البيانات
        $request->validate([
            'scheduled_at' => 'required|date|after:now',
            'amount_paid'  => 'required|numeric|min:0',
        ]);

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

        /*
    |--------------------------------------------------------------------------
    | 1) التحقق من اليوم ضمن نطاق الأيام المتاحة
    |--------------------------------------------------------------------------
    */

        // استخراج اليوم من تاريخ الحجز (إنجليزي)
        $dayNameEnglish = \Carbon\Carbon::parse($request->scheduled_at)->format('l');
        $dayArabic = $this->convertDayToArabic($dayNameEnglish);

        // توليد نطاق الأيام المتاحة
        $availableDays = $this->generateDaysRange(
            $service->available_days[0],
            $service->available_days[count($service->available_days) - 1]
        );

        if (!in_array($dayArabic, $availableDays)) {
            return response()->json(['message' => 'اليوم المختار غير متاح للحجز'], 400);
        }

        /*
    |--------------------------------------------------------------------------
    | 2) التحقق من الساعة ضمن نطاق الساعات المتاحة
    |--------------------------------------------------------------------------
    */

        $hour = \Carbon\Carbon::parse($request->scheduled_at)->format('H:i');

        $availableHours = $this->generateHoursRange(
            $service->available_hours[0],
            $service->available_hours[count($service->available_hours) - 1]
        );

        if (!in_array($hour, $availableHours)) {
            return response()->json(['message' => 'الساعة المختارة غير متاحة للحجز'], 400);
        }

        /*
    |--------------------------------------------------------------------------
    | 3) التحقق من عدم وجود حجز سابق لنفس الوقت
    |--------------------------------------------------------------------------
    */

        $existingBooking = Booking::where('service_id', $service_id)
            ->where('scheduled_at', $request->scheduled_at)
            ->first();

        if ($existingBooking) {
            return response()->json(['message' => 'هذا الموعد محجوز مسبقًا'], 400);
        }

        /*
    |--------------------------------------------------------------------------
    | 4) التحقق من رصيد المستخدم
    |--------------------------------------------------------------------------
    */

        $balance = Balance::firstOrCreate(
            ['user_id' => $user->id],
            ['current_balance' => 0]
        );

        if ($balance->current_balance < $service->book_price) {
            return response()->json(['message' => 'الرصيد غير كافٍ لإتمام الحجز'], 400);
        }

        // رصيد المزود
        $providerBalance = Balance::firstOrCreate(
            ['user_id' => $service->provider_id],
            ['current_balance' => 0]
        );

        /*
    |--------------------------------------------------------------------------
    | 5) تنفيذ عملية الحجز داخل Transaction
    |--------------------------------------------------------------------------
    */

        DB::beginTransaction();

        try {
            // خصم من المستخدم
            $balance->current_balance -= $service->book_price;
            $balance->save();

            // إضافة للمزود
            $providerBalance->current_balance += $service->book_price;
            $providerBalance->save();

            // إنشاء الحجز
            $booking = Booking::create([
                'user_id'      => $user->id,
                'service_id'   => $service->id,
                'scheduled_at' => $request->scheduled_at,
                'amount_paid'  => $request->amount_paid,
                'status'       => 'pending',
            ]);

            DB::commit();

            return response()->json([
                'message' => 'تم إنشاء الحجز بنجاح',
                'data'    => $booking,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'حدث خطأ أثناء الحجز', 'error' => $e->getMessage()], 500);
        }
    }


    // حجوزات المستخدم
    public function myBookings()
    {
        $user = Auth::user();
        $bookings = Booking::where('user_id', $user->id)
            ->with('service')
            ->get();

        return response()->json($bookings);
    }

    // حجوزات خدمة معينة
    public function serviceBookings($service_id)
    {
        $service = Service::findOrFail($service_id);

        $bookings = Booking::where('service_id', $service->id)
            ->with('user')
            ->get();

        return response()->json($bookings);
    }

    // تحديث حالة الحجز
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,confirmed,cancelled,completed',
        ]);

        $booking = Booking::findOrFail($id);

        $booking->status = $request->status;
        $booking->save();

        return response()->json([
            'message' => 'تم تحديث حالة الحجز',
            'data' => $booking,
        ]);
    }

    // حذف الحجز
    public function destroy($id)
    {
        $booking = Booking::findOrFail($id);
        $booking->delete();

        return response()->json(['message' => 'تم حذف الحجز']);
    }

    // تحويل أيام الإنجليزية إلى العربية
    private function convertDayToArabic($day)
    {
        return [
            'Saturday' => 'السبت',
            'Sunday' => 'الأحد',
            'Monday' => 'الاثنين',
            'Tuesday' => 'الثلاثاء',
            'Wednesday' => 'الأربعاء',
            'Thursday' => 'الخميس',
            'Friday' => 'الجمعة',
        ][$day] ?? $day;
    }
    private function generateDaysRange($startDay, $endDay)
    {
        $days = ['السبت', 'الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة'];

        $startIndex = array_search($startDay, $days);
        $endIndex   = array_search($endDay, $days);

        if ($startIndex === false || $endIndex === false) {
            return [];
        }

        if ($startIndex <= $endIndex) {
            return array_slice($days, $startIndex, $endIndex - $startIndex + 1);
        }

        // في حال كان النطاق يمر عبر نهاية الأسبوع
        return array_merge(
            array_slice($days, $startIndex),
            array_slice($days, 0, $endIndex + 1)
        );
    }
    private function generateHoursRange($startHour, $endHour)
    {
        $start = \Carbon\Carbon::createFromFormat('H:i', $startHour);
        $end   = \Carbon\Carbon::createFromFormat('H:i', $endHour);

        $hours = [];

        while ($start->lte($end)) {
            $hours[] = $start->format('H:i');
            $start->addHour();
        }

        return $hours;
    }
}
