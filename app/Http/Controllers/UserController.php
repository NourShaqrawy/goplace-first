<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Balance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('balance')->get();

        return response()->json([
            'message' => 'All users retrieved successfully',
            'data' => $users
        ]);
    }

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

    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'role'     => 'required|string|max:50',
            'email'    => 'required|email|unique:users,email',
            'phone'    => 'nullable|string|unique:users,phone',
            'password' => 'required|string|min:6',
            'current_balance' => 'numeric|min:0'
        ]);

        $user = User::create([
            'name'     => $request->name,
            'role'     => $request->role,
            'email'    => $request->email,
            'phone'    => $request->phone,
            'status'   => 'active',
            'password' => Hash::make($request->password),
        ]);

        Balance::create([
            'user_id' => $user->id,
            'current_balance' => $request->current_balance ?? 0.00,
        ]);

        $user->load('balance');

        return response()->json([
            'message' => 'User created successfully',
            'data' => $user
        ], 201);
    }
        public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $balance = Balance::where('user_id', $user->id)->first();
        if ($balance) {
            $balance->delete();
        }

        // حذف المستخدم
        $user->delete();

        return response()->json([
            'message' => 'User and balance deleted successfully'
        ]);
    }
}
