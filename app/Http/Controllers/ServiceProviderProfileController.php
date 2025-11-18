<?php
// app/Http/Controllers/ServiceProviderProfileController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ServiceProviderProfile;

class ServiceProviderProfileController extends Controller
{
    public function myProfile()
    {
        $user = Auth::user();

        if ($user->role !== 'service_provider') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $profile = ServiceProviderProfile::where('user_id', $user->id)->first();

        if (!$profile) {
            return response()->json(['message' => 'Profile not found'], 404);
        }

        return response()->json($profile);
    }

    public function upsert(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== 'service_provider') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'company_name' => 'nullable|string|max:255',
            'location_address' => 'required|string|max:255',
        ]);

        $profile = ServiceProviderProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'company_name' => $request->company_name,
                'location_address' => $request->location_address,
            ]
        );

        return response()->json([
            'message' => 'Profile saved successfully',
            'data' => $profile,
        ]);
    }

    public function show($user_id)
    {
        $profile = ServiceProviderProfile::where('user_id', $user_id)->first();

        if (!$profile) {
            return response()->json(['message' => 'Profile not found'], 404);
        }

        return response()->json($profile);
    }
}
