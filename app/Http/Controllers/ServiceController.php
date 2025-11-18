<?php
// app/Http/Controllers/ServiceController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Service;

class ServiceController extends Controller
{
    public function index()
    {
        $services = Service::where('is_approved', true)
            ->where('capacity', '>', 0)
            ->get();

        return response()->json($services);
    }

    public function myServices()
    {
        $user = Auth::user();

        if ($user->role !== 'service_provider') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $services = Service::where('provider_id', $user->id)->get();

        return response()->json($services);
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== 'service_provider') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'required|string',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'capacity' => 'required|integer|min:1',
            'category_id' => 'required|exists:categories,id',
        ]);

        $service = Service::create([
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'capacity' => $request->capacity,
            'provider_id' => $user->id,
            'category_id' => $request->category_id,
            'is_approved' => false, 
        ]);

        return response()->json([
            'message' => 'Service proposed successfully, pending approval',
            'data' => $service,
        ], 201);
    }

  
    public function approve($id)
    {
        $user = Auth::user();

        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $service = Service::find($id);

        if (!$service) {
            return response()->json(['message' => 'Service not found'], 404);
        }

        $service->is_approved = true;
        $service->save();

        return response()->json(['message' => 'Service approved']);
    }

 
    // public function book($id)
    // {
    //     $user = Auth::user();

    //     if (!$user) {
    //         return response()->json(['message' => 'Unauthenticated'], 401);
    //     }

    //     $service = Service::where('is_approved', true)->find($id);

    //     if (!$service) {
    //         return response()->json(['message' => 'Service not found or not approved'], 404);
    //     }

    //     if ($service->capacity < 1) {
    //         return response()->json(['message' => 'Service is fully booked'], 400);
    //     }

    //     $service->capacity -= 1;
    //     $service->save();

    //     return response()->json(['message' => 'Service booked successfully']);
    // }
}
