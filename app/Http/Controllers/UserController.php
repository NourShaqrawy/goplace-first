<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Balance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * عرض جميع المستخدمين مع أرصدتهم (للمسؤولين مثلاً)
     */
    public function index()
    {
        $users = User::with('balance')->get();

        return response()->json([
            'message' => 'All users retrieved successfully',
            'data' => $users
        ]);
    }

    /**
     * عرض بيانات مستخدم معين
     */
    public function show($id)
    {
        $user = User::with('balance')->find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return response()->json([
            'message' => 'User retrieved successfully',
            'data' => $user
        ]);
    }

    /**
     * إنشاء مستخدم جديد (Register/Admin Store)
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'            => 'required|string|max:255',
            'role'            => 'required|string|max:50',
            'email'           => 'required|email|unique:users,email',
            'phone'           => 'nullable|string|unique:users,phone',
            'password'        => 'required|string|min:6',
            'current_balance' => 'numeric|min:0',
            'image'           => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('users', 'public');
        }

        $user = User::create([
            'name'     => $request->name,
            'role'     => $request->role,
            'email'    => $request->email,
            'phone'    => $request->phone,
            'status'   => 'active',
            'password' => Hash::make($request->password),
            'image'    => $imagePath,
        ]);

        Balance::create([
            'user_id' => $user->id,
            'current_balance' => $request->current_balance ?? 0.00,
        ]);

        $user->load('balance');

        return response()->json([
            'message' => 'User created successfully',
            'data'    => $user
        ], 201);
    }

    /**
     * تحديث بيانات المستخدم (يسمح فقط للمستخدم بتعديل نفسه)
     */
    public function update(Request $request)
    {
        /** @var \App\Models\User $user */
        // استخدام Auth::user لضمان أن التعديل يتم فقط لصاحب التوكن المرسل
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $request->validate([
            'name'     => 'sometimes|string|max:255',
            'email'    => 'sometimes|email|unique:users,email,' . $user->id,
            'phone'    => 'sometimes|string|unique:users,phone,' . $user->id,
            'password' => 'sometimes|string|min:6|confirmed',
            'image'    => 'sometimes|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        // تحديث البيانات الأساسية (باستثناء الحقول الحساسة مثل الـ Role)
        $user->fill($request->only(['name', 'email', 'phone']));

        if ($request->has('password')) {
            $user->password = Hash::make($request->password);
        }

        // معالجة تحديث الصورة
        if ($request->hasFile('image')) {
            // حذف الصورة القديمة إذا وجدت
            if ($user->image) {
                Storage::disk('public')->delete($user->image);
            }
            // رفع الصورة الجديدة
            $user->image = $request->file('image')->store('users', 'public');
        }

        $user->save();
        $user->load('balance');

        return response()->json([
            'message' => 'Profile updated successfully',
            'data'    => $user
        ]);
    }

    /**
     * حذف مستخدم ورصيده
     */
    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // حذف الصورة من التخزين قبل حذف السجل
        if ($user->image) {
            Storage::disk('public')->delete($user->image);
        }

        // حذف الرصيد المرتبط (سيُحذف تلقائياً إذا وضعت onDelete cascade في الـ Migration)
        if ($user->balance) {
            $user->balance->delete();
        }

        $user->delete();

        return response()->json([
            'message' => 'User and balance deleted successfully'
        ]);
    }
    
}