<?php

namespace App\Http\Controllers;

use App\Models\Rating;
use App\Models\Service;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RatingController extends Controller
{
    // عرض متوسط التقييم وعدد التقييمات
    public function stats($serviceId)
    {
        $avg = Rating::where('service_id', $serviceId)->avg('stars');
        $count = Rating::where('service_id', $serviceId)->count();

        $comments = Rating::where('service_id', $serviceId)
            ->whereNotNull('comment')
            ->with('user:name')
            ->get(['id', 'stars', 'comment', 'user_id', 'created_at']);

        return response()->json([
            'average' => round($avg, 2),
            'count' => $count,
            'comments' => $comments
        ]);
    }

    // إضافة تقييم
    public function store(Request $request, $serviceId)
    {
        $service = Service::findOrFail($serviceId);

        $booking = Booking::where('service_id', $serviceId)
            ->where('user_id', Auth::id())
            ->where('status', 'completed')
            ->first();

        if (!$booking) {
            return response()->json(['message' => 'لا يمكنك تقييم الخدمة قبل حجزها'], 403);
        }

        $existing = Rating::where('booking_id', $booking->id)->first();
        if ($existing) {
            return response()->json(['message' => 'لقد قمت بتقييم هذه الخدمة مسبقًا'], 403);
        }

        $validated = $request->validate([
            'stars' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $rating = Rating::create([
            'user_id' => Auth::id(),
            'service_id' => $serviceId,
            'booking_id' => $booking->id,
            'stars' => $validated['stars'],
            'comment' => $validated['comment'] ?? null,
        ]);

        return response()->json(['message' => 'تم إضافة التقييم', 'rating' => $rating]);
    }

    // تعديل التقييم
    public function update(Request $request, $id)
    {
        $rating = Rating::findOrFail($id);

        if ($rating->user_id !== Auth::id()) {
            return response()->json(['message' => 'غير مسموح لك بتعديل هذا التقييم'], 403);
        }

        $validated = $request->validate([
            'stars' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $rating->update($validated);

        return response()->json(['message' => 'تم تعديل التقييم', 'rating' => $rating]);
    }

    // حذف التقييم (المستخدم الخاص او الادمن)
    public function destroy($id)
    {
        $rating = Rating::findOrFail($id);

        if ($rating->user_id !== Auth::id() && Auth::user()->role !== 'admin') {
            return response()->json(['message' => 'غير مسموح لك بحذف هذا التقييم'], 403);
        }

        $rating->delete();
        return response()->json(['message' => 'تم حذف التقييم بنجاح']);
    }
}
