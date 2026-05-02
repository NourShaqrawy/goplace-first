<?php

namespace App\Http\Controllers;

use App\Models\Topup;
use App\Models\Balance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TopupController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'transaction_id' => 'required|string',
            'receipt_image' => 'nullable|image|max:2048'
        ]);

        $path = null;
        if ($request->hasFile('receipt_image')) {
            $path = $request->file('receipt_image')->store('receipts', 'public');
        }

        $topup = Topup::create([
            'user_id' => Auth::id(),
            'amount' => $request->amount,
            'transaction_id' => $request->transaction_id,
            'receipt_image' => $path,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Topup request submitted, pending admin approval',
            'data' => $topup
        ], 201);
    }

    public function approve($id)
    {
        $topup = Topup::findOrFail($id);

        if ($topup->status !== 'pending') {
            return response()->json(['message' => 'Topup already processed'], 400);
        }

        $topup->status = 'approved';
        $topup->save();

        $balance = Balance::firstOrCreate(
            ['user_id' => $topup->user_id],
            ['current_balance' => 0.00]
        );

        $balance->current_balance += $topup->amount;
        $balance->save();

        return response()->json([
            'message' => 'Topup approved and balance updated',
            'data' => [
                'topup' => $topup,
                'balance' => $balance
            ]
        ]);
    }

    public function reject($id)
    {
        $topup = Topup::findOrFail($id);

        if ($topup->status !== 'pending') {
            return response()->json(['message' => 'Topup already processed'], 400);
        }

        $topup->status = 'rejected';
        $topup->save();

        return response()->json([
            'message' => 'Topup rejected',
            'data' => $topup
        ]);
    }

    public function index()
    {
        $topups = Topup::with('user')->latest()->get()->map(function ($topup) {
            return [
                'id'            => $topup->id,
                'user_id'       => $topup->user_id,
                'amount'        => $topup->amount,
                'transaction_id'        => $topup->transaction_id,
                'receipt_image' => $topup->receipt_image
                    ? asset('storage/' . $topup->receipt_image)
                    : null,
                'status'        => $topup->status,
                'created_at'    => $topup->created_at,
                'updated_at'    => $topup->updated_at,
                'user'          => $topup->user,
            ];
        });

        return response()->json([
            'message' => 'All topup requests',
            'data'    => $topups
        ]);
    }
}
