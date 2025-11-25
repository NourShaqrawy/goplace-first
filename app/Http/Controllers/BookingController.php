<?php

namespace App\Http\Controllers;

use App\Models\Balance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Booking;
use App\Models\Service;

class BookingController extends Controller
{
    public function book(Request $request, $service_id)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $request->validate([
            'scheduled_at' => 'required|date|after:now',
            'amount_paid' => 'required|numeric|min:0',
        ]);

        $service = Service::where('id', $service_id)
            ->where('is_approved', true)
            ->first();

        if (!$service) {
            return response()->json(['message' => 'Service not found or not approved'], 404);
        }


        if ($service->capacity < 1) {
            return response()->json(['message' => 'Service is fully booked'], 400);
        }

        $balance = Balance::where('user_id', $user->id)->first();


        if (!$balance) {
            return response()->json(['message' => 'Balance record not found'], 404);
        }

        if ($balance->current_balance < $service->price) {
            return response()->json(['message' => 'Insufficient balance'], 400);
        }
        $balance->current_balance -= $service->price;
        $balance->save();
        $service->capacity -= 1;
        $service->save();

        $booking = Booking::create([
            'user_id' => $user->id,
            'service_id' => $service->id,
            'scheduled_at' => $request->scheduled_at,
            'amount_paid' => $request->amount_paid,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Booking created successfully',
            'data' => $booking,
        ], 201);
    }

    public function myBookings()
    {
        $user = Auth::user();
        $bookings = Booking::where('user_id', $user->id)->with('service')->get();

        return response()->json($bookings);
    }

    public function serviceBookings($service_id)
    {
        $service = Service::findOrFail($service_id);
        $bookings = Booking::where('service_id', $service->id)->with('user')->get();

        return response()->json($bookings);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,confirmed,cancelled,completed',
        ]);

        $booking = Booking::findOrFail($id);
        $booking->status = $request->status;
        $booking->save();

        return response()->json([
            'message' => 'Booking status updated successfully',
            'data' => $booking,
        ]);
    }

    public function destroy($id)
    {
        $booking = Booking::findOrFail($id);
        $booking->delete();

        return response()->json([
            'message' => 'Booking deleted successfully',
        ]);
    }
}
