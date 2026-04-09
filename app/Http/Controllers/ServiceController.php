<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ServiceController extends Controller
{
    public function indexApproved()
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
                    'time_to_complete' => $service->time_to_complete,
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
                'time_to_complete' => $service->time_to_complete,
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

        // السماح للمستخدم العادي أيضاً برؤية خدماته
        if (!in_array($user->role, ['service_provider', 'user'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $services = Service::where('provider_id', $user->id)
            ->get()
            ->map(function ($service) {
                return [
                    'id'               => $service->id,
                    'name'             => $service->name,
                    'is_approved'      => $service->is_approved,
                ];
            });

        return response()->json([
            'message' => 'My services list',
            'data'    => $services,
        ]);
    }
    // public function store(Request $request)
    // {
    //     $user = Auth::user();

    //     if ($user->role !== 'service_provider') {
    //         return response()->json(['message' => 'Unauthorized'], 403);
    //     }

    //     $request->validate([
    //         'name'             => 'required|string',
    //         'description'      => 'required|string',
    //         'fullPrice'        => 'required|numeric|min:0',
    //         'book_price'       => 'required|numeric|min:0',
    //         'city'             => 'required|string',
    //         'location'         => 'required|string',
    //         'time_to_complete' => 'required|string',
    //         'available_days'   => 'required|array',
    //         'available_hours'  => 'required|array',
    //         'mainImage'        => 'image',
    //         'otherImages'      => 'array',
    //         'otherImages.*'    => 'image',
    //         'category_id'      => 'required|exists:categories,id',
    //     ]);

    //     // الصورة الرئيسية
    //     $mainImagePath = null;

    //     if ($request->hasFile('mainImage')) {
    //         $mainImagePath = $request->file('mainImage')->store('services', 'public');
    //     }

    //     // الصور الإضافية
    //     $otherImagesPaths = [];
    //     if ($request->hasFile('otherImages')) {
    //         foreach ($request->file('otherImages') as $img) {
    //             $otherImagesPaths[] = $img->store('services', 'public');
    //         }
    //     }

    //     $service = Service::create([
    //         'name'             => $request->name,
    //         'description'      => $request->description,
    //         'fullPrice'        => $request->fullPrice,
    //         'book_price'       => $request->book_price,
    //         'city'             => $request->city,
    //         'location'         => $request->location,
    //         'time_to_complete' => $request->time_to_complete,
    //         'available_days'   => $request->available_days,
    //         'available_hours'  => $request->available_hours,
    //         'provider_id'      => $user->id,
    //         'category_id'      => $request->category_id,
    //         'is_approved'      => false,
    //         'main_image'       => $mainImagePath,
    //         'other_images'     => $otherImagesPaths,
    //     ]);

    //     return response()->json([
    //         'message' => 'Service proposed successfully, pending approval',
    //         'data'    => [
    //             'id'          => $service->id,
    //             'name'        => $service->name,
    //             'mainImage'   => $service->main_image_url,
    //             'otherImages' => $service->other_images_url,
    //         ],
    //     ], 201);
    // }
    public function store(Request $request)
    {
        $user = Auth::user();

        // التحقق من الصلاحية: نسمح للمستخدم العادي ومقدم الخدمة والمسؤول
        if (!in_array($user->role, ['user', 'service_provider', 'admin'])) {
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
            'mainImage'        => 'image',
            'otherImages'      => 'array',
            'otherImages.*'    => 'image',
            'category_id'      => 'required|exists:categories,id',
        ]);

        // رفع الصورة الرئيسية
        $mainImagePath = null;
        if ($request->hasFile('mainImage')) {
            $mainImagePath = $request->file('mainImage')->store('services', 'public');
        }

        // رفع الصور الإضافية
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
            'is_approved'      => false, // دائماً false بانتظار موافقة الأدمن
            'main_image'       => $mainImagePath,
            'other_images'     => $otherImagesPaths,
        ]);

        return response()->json([
            'message' => 'تم تقديم الخدمة بنجاح. سيتم ترقية حسابك إلى "مقدم خدمة" فور موافقة المسؤول.',
            'data'    => [
                'id'    => $service->id,
                'name'  => $service->name,
            ],
        ], 201);
    }

    public function approve($id)
    {
        $admin = Auth::user();

        if ($admin->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // جلب الخدمة مع بيانات صاحبها باستخدام العلاقة provider
        $service = Service::with('provider')->find($id);

        if (!$service) {
            return response()->json(['message' => 'Service not found'], 404);
        }

        // 1) الموافقة على الخدمة
        $service->is_approved = true;
        $service->save();

        // 2) ترقية المستخدم إذا كان مستخدماً عادياً
        $userWhoProposed = $service->provider;

        if ($userWhoProposed && $userWhoProposed->role === 'user') {
            $userWhoProposed->role = 'service_provider';
            $userWhoProposed->save();
        }

        return response()->json([
            'message' => 'تمت الموافقة على الخدمة بنجاح، وتحديث رتبة المستخدم إلى مقدم خدمة.'
        ]);
    }
    public function update(Request $request, $id)
    {
        // 1) جلب الخدمة
        $service = Service::findOrFail($id);

        // 2) التحقق أن المستخدم الحالي هو صاحب الخدمة
        if ($service->provider_id !== Auth::id()) {
            return response()->json([
                'message' => 'غير مسموح لك بتعديل هذه الخدمة'
            ], 403);
        }

        // 3) التحقق من البيانات
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',

            'fullPrice' => 'sometimes|numeric|min:0',
            'book_price' => 'sometimes|numeric|min:0',

            'main_image' => 'sometimes|image|mimes:jpg,jpeg,png|max:2048',
            'other_images.*' => 'sometimes|image|mimes:jpg,jpeg,png|max:2048',

            'city' => 'sometimes|string|max:255',
            'location' => 'sometimes|string|max:255',

            'time_to_complete' => 'sometimes|string|max:255',
            'available_days' => 'sometimes|array',
            'available_hours' => 'sometimes|array',

            'category_id' => 'sometimes|exists:categories,id',
        ]);

        // 4) تحديث الصورة الرئيسية إن وُجدت
        if ($request->hasFile('main_image')) {
            $path = $request->file('main_image')->store('services/main', 'public');
            $validated['main_image'] = $path;
        }

        // 5) تحديث الصور الإضافية إن وُجدت
        if ($request->hasFile('other_images')) {
            $paths = [];
            foreach ($request->file('other_images') as $image) {
                $paths[] = $image->store('services/others', 'public');
            }
            $validated['other_images'] = json_encode($paths);
        }

        // 6) عند أي تعديل → تصبح الخدمة غير معتمدة
        $validated['is_approved'] = false;

        // 7) تحديث البيانات
        $service->update($validated);

        return response()->json([
            'message' => 'تم تحديث الخدمة بنجاح، وتم تحويلها إلى غير معتمدة بانتظار المراجعة',
            'service' => $service
        ]);
    }
    public function indexNotApproved()
    {

        $user = Auth::user();

        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $services = Service::where('is_approved', false)
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
                    'time_to_complete' => $service->time_to_complete,
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
            'message' => 'unApproved services list',
            'data'    => $services,
        ]);
    }
    public function destroy($id)
    {
        // 1) جلب الخدمة
        $service = Service::findOrFail($id);

        // 2) إذا كان المستخدم Admin → مسموح له الحذف مباشرة
        if (Auth::user()->role === 'admin') {
            $service->delete();

            return response()->json([
                'message' => 'تم حذف الخدمة بنجاح بواسطة الأدمن'
            ]);
        }

        // 3) إذا كان المستخدم مقدم خدمة → يتحقق أنه صاحب الخدمة
        if (Auth::user()->role === 'service_provider') {

            if ($service->provider_id !== Auth::id()) {
                return response()->json([
                    'message' => 'غير مسموح لك بحذف هذه الخدمة'
                ], 403);
            }

            $service->delete();

            return response()->json([
                'message' => 'تم حذف الخدمة بنجاح'
            ]);
        }

        // 4) أي دور آخر → ممنوع
        return response()->json([
            'message' => 'غير مسموح لك بتنفيذ هذا الإجراء'
        ], 403);
    }
}
