<?php

namespace App\Http\Controllers;

use App\Models\Balance;
use Illuminate\Http\Request;

class BalanceController extends Controller
{
    // عرض رصيد مستخدم محدد
    public function show($userId)
    {
        $balance = Balance::where('user_id', $userId)->first();

        if (!$balance) {
            return response()->json(['message' => 'Balance not found'], 404);
        }

        return response()->json([
            'message' => 'Balance retrieved successfully',
            'data' => $balance
        ]);
    }

    // إنشاء رصيد جديد لمستخدم (عادة عند التسجيل أو من قبل الأدمن)
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id|unique:balances,user_id',
            'current_balance' => 'numeric|min:0'
        ]);

        $balance = Balance::create([
            'user_id' => $request->user_id,
            'current_balance' => $request->current_balance ?? 0.00,
        ]);

        return response()->json([
            'message' => 'Balance created successfully',
            'data' => $balance
        ], 201);
    }

    // تحديث الرصيد (إيداع أو سحب)
    public function update(Request $request, $userId)
    {
        $balance = Balance::where('user_id', $userId)->first();

        if (!$balance) {
            return response()->json(['message' => 'Balance not found'], 404);
        }

        $request->validate([
            'amount' => 'required|numeric'
        ]);

        $newBalance = $balance->current_balance + $request->amount;

        if ($newBalance < 0) {
            return response()->json(['message' => 'Insufficient balance'], 400);
        }

        $balance->current_balance = $newBalance;
        $balance->save();

        return response()->json([
            'message' => 'Balance updated successfully',
            'data' => $balance
        ]);
    }

    // حذف رصيد مستخدم (نادراً ما تحتاجه)
    public function destroy($userId)
    {
        $balance = Balance::where('user_id', $userId)->first();

        if (!$balance) {
            return response()->json(['message' => 'Balance not found'], 404);
        }

        $balance->delete();

        return response()->json(['message' => 'Balance deleted successfully']);
    }
}
