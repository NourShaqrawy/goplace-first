<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Service;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    // عرض التعليقات الخاصة بخدمة
    public function index($serviceId)
    {
        $comments = Comment::with('user:id,name')
            ->where('service_id', $serviceId)
            ->latest()
            ->get();

        return response()->json($comments);
    }

    // إضافة تعليق
    public function store(Request $request, $serviceId)
    {
        $service = Service::findOrFail($serviceId);

        $booking = Booking::where('service_id', $serviceId)
            ->where('user_id', Auth::id())
            ->where('status', 'completed')
            ->first();

        if (!$booking) {
            return response()->json(['message' => 'لا يمكنك إضافة تعليق قبل حجز الخدمة'], 403);
        }

        $validated = $request->validate([
            'comment' => 'required|string|max:2000',
        ]);

        $comment = Comment::create([
            'user_id' => Auth::id(),
            'service_id' => $serviceId,
            'booking_id' => $booking->id,
            'comment' => $validated['comment'],
        ]);

        return response()->json(['message' => 'تم إضافة التعليق', 'comment' => $comment]);
    }

    // تعديل التعليق
    public function update(Request $request, $id)
    {
        $comment = Comment::findOrFail($id);

        if ($comment->user_id !== Auth::id()) {
            return response()->json(['message' => 'غير مسموح لك بتعديل هذا التعليق'], 403);
        }

        $validated = $request->validate([
            'comment' => 'required|string|max:2000',
        ]);

        $comment->update($validated);

        return response()->json(['message' => 'تم تعديل التعليق', 'comment' => $comment]);
    }

    // حذف التعليق (الأدمن أو صاحب التعليق)
    public function destroy($id)
    {
        $comment = Comment::findOrFail($id);

        if (Auth::user()->role !== 'admin' && $comment->user_id !== Auth::id()) {
            return response()->json(['message' => 'غير مسموح لك بحذف هذا التعليق'], 403);
        }

        $comment->delete();

        return response()->json(['message' => 'تم حذف التعليق']);
    }
}
