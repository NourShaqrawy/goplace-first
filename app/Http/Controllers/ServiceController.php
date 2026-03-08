<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ServiceController extends Controller
{
    public function index()
    {
        $services = Service::where('is_approved', true)
            ->get()
            ->map(function ($service) {
                return [
                    'id'              => $service->id,
                    'name'            => $service->name,
                    'description'     => $service->description,
                    'fullPrice'       => $service->fullPrice,
                    'book_price'      => $service->book_price,
                    'city'            => $service->city,
                    'location'        => $service->location,
                    'time_to_complete'=> $service->time_to_complete,
                    'available_days'  => $service->available_days,
                    'available_hours' => $service->available_hours,
                    'provider_id'     => $service->provider_id,
                    'category_id'     => $service->category_id,
                    'is_approved'     => $service->is_approved,
                    'mainImage'       => $service->main_image_url,
                    'otherImages'     => $service->other_images_url,
                    'created_at'      => $service->created_at,
                    'updated_at'      => $service->updated_at,
                ];
            });

        return response()->json([
            'message' => 'Approved services list',
            'data'    => $services,
        ]);
    }

    public function show($id)
    {
        $service = Service::where('id', $id)
            ->where('is_approved', true)
            ->first();

        if (!$service) {
            return response()->json(['message' => 'Service not found'], 404);
        }

        return response()->json([
            'message' => 'Service details',
            'data'    => [
                'id'              => $service->id,
                'name'            => $service->name,
                'description'     => $service->description,
                'fullPrice'       => $service->fullPrice,
                'book_price'      => $service->book_price,
                'city'            => $service->city,
                'location'        => $service->location,
                'time_to_complete'=> $service->time_to_complete,
                'available_days'  => $service->available_days,
                'available_hours' => $service->available_hours,
                'provider_id'     => $service->provider_id,
                'category_id'     => $service->category_id,
                'is_approved'     => $service->is_approved,
                'mainImage'       => $service->main_image_url,
                'otherImages'     => $service->other_images_url,
                'created_at'      => $service->created_at,
                'updated_at'      => $service->updated_at,
            ],
        ]);
    }

    public function myServices()
    {
        $user = Auth::user();

        if ($user->role !== 'service_provider') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $services = Service::where('provider_id', $user->id)
            ->get()
            ->map(function ($service) {
                return [
                    'id'              => $service->id,
                    'name'            => $service->name,
                    'description'     => $service->description,
                    'fullPrice'       => $service->fullPrice,
                    'book_price'      => $service->book_price,
                    'city'            => $service->city,
                    'location'        => $service->location,
                    'time_to_complete'=> $service->time_to_complete,
                    'available_days'  => $service->available_days,
                    'available_hours' => $service->available_hours,
                    'provider_id'     => $service->provider_id,
                    'category_id'     => $service->category_id,
                    'is_approved'     => $service->is_approved,
                    'mainImage'       => $service->main_image_url,
                    'otherImages'     => $service->other_images_url,
                    'created_at'      => $service->created_at,
                    'updated_at'      => $service->updated_at,
                ];
            });

        return response()->json([
            'message' => 'My services',
            'data'    => $services,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== 'service_provider') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name'             => 'required|string',
            'description'      => 'required|string',
            'fullPrice'        => 'required|numeric|min:0',
            'book_price'       => 'required|numeric|min:0',
            'city'             => 'required|string',
            'location'         => 'required|string',
            'time_to_complete' => 'required|string',
            'available_days'   => 'required|array',
            'available_hours'  => 'required|array',
            'mainImage'        => 'required|image',
            'otherImages'      => 'array',
            'otherImages.*'    => 'image',
            'category_id'      => 'required|exists:categories,id',
        ]);

        // الصورة الرئيسية
        $mainImagePath = $request->file('mainImage')->store('services', 'public');

        // الصور الإضافية
        $otherImagesPaths = [];
        if ($request->hasFile('otherImages')) {
            foreach ($request->file('otherImages') as $img) {
                $otherImagesPaths[] = $img->store('services', 'public');
            }
        }

        $service = Service::create([
            'name'             => $request->name,
            'description'      => $request->description,
            'fullPrice'        => $request->fullPrice,
            'book_price'       => $request->book_price,
            'city'             => $request->city,
            'location'         => $request->location,
            'time_to_complete' => $request->time_to_complete,
            'available_days'   => $request->available_days,
            'available_hours'  => $request->available_hours,
            'provider_id'      => $user->id,
            'category_id'      => $request->category_id,
            'is_approved'      => false,
            'main_image'       => $mainImagePath,
            'other_images'     => $otherImagesPaths,
        ]);

        return response()->json([
            'message' => 'Service proposed successfully, pending approval',
            'data'    => [
                'id'          => $service->id,
                'name'        => $service->name,
                'mainImage'   => $service->main_image_url,
                'otherImages' => $service->other_images_url,
            ],
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
}
