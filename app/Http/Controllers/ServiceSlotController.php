<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ServiceSlot;
use App\Models\Service;

class ServiceSlotController extends Controller
{
    public function store(Request $request, $service_id)
    {
        $user = Auth::user();

        if ($user->role !== 'service_provider') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'slot_date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'available_capacity' => 'required|integer|min:1',
        ]);

        $service = Service::findOrFail($service_id);

        $slot = ServiceSlot::create([
            'service_id' => $service->id,
            'slot_date' => $request->slot_date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'available_capacity' => $request->available_capacity,
        ]);

        return response()->json([
            'message' => 'Slot created successfully',
            'data' => $slot,
        ], 201);
    }

    public function availableSlots($service_id)
    {
        $slots = ServiceSlot::where('service_id', $service_id)
            ->where('available_capacity', '>', 0)
            ->orderBy('slot_date')
            ->orderBy('start_time')
            ->get();

        return response()->json([
            'service_id' => $service_id,
            'available_slots' => $slots
        ]);
    }

    public function update(Request $request, $id)
    {
        $slot = ServiceSlot::findOrFail($id);

        $request->validate([
            'slot_date' => 'sometimes|date|after_or_equal:today',
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i|after:start_time',
            'available_capacity' => 'sometimes|integer|min:0',
        ]);

        $slot->update($request->only(['slot_date', 'start_time', 'end_time', 'available_capacity']));

        return response()->json([
            'message' => 'Slot updated successfully',
            'data' => $slot,
        ]);
    }

    public function destroy($id)
    {
        $slot = ServiceSlot::findOrFail($id);
        $slot->delete();

        return response()->json([
            'message' => 'Slot deleted successfully',
        ]);
    }
}
