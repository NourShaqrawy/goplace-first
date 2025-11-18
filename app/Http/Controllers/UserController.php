<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Telegram\Bot\Api;

class UserController extends Controller
{
    public function store(Request $request)
    {
        $user = User::create($request->all());

        $telegram = new Api(config('services.telegram.bot_token'));
        $telegram->sendMessage([
            'chat_id' => '@CR7MD1',
            'text' => "تم تسجيل مستخدم جديد: {$user->name}",
        ]);

        return redirect()->back()->with('success', 'User created and notified!');
    }
}
