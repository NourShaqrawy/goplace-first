<?php

namespace App\Http\Controllers;

use App\Models\Balance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BalanceController extends Controller
{
    public function index()
    {
        $balances = Balance::with('user')->get();

        return response()->json([
            'message' => 'All balances retrieved successfully',
            'data' => $balances
        ]);
    }

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

    public function myBalance()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $balance = Balance::where('user_id', $user->id)->first();

        if (!$balance) {
            return response()->json(['message' => 'Balance not found'], 404);
        }

        return response()->json([
            'message' => 'Your balance retrieved successfully',
            'data' => $balance
        ]);
    }

    public function saveBalance(Request $request, $userId)
{
    $request->validate([
        'amount' => 'required|numeric' // يمكن أن تكون موجبة أو سالبة
    ]);

    // البحث عن الرصيد أو إنشاؤه إذا لم يوجد
    $balance = Balance::firstOrCreate(
        ['user_id' => $userId],
        ['current_balance' => 0.00]
    );

    $newBalance = $balance->current_balance + $request->amount;

    if ($newBalance < 0) {
        return response()->json(['message' => 'Insufficient balance'], 400);
    }

    $balance->current_balance = $newBalance;
    $balance->save();

    return response()->json([
        'message' => $balance->wasRecentlyCreated 
            ? 'Balance created successfully' 
            : 'Balance updated successfully',
        'data' => $balance
    ], $balance->wasRecentlyCreated ? 201 : 200);
}


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
