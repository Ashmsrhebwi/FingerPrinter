<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
class FingerprintController extends Controller
{
    public function startRegister(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer'
        ]);

        cache()->put('finger_register', true);
        cache()->put('finger_user_id', $request->user_id);

        return response()->json([
            'message' => 'Parmak kaydı başlatıldı'
        ]);
    }

public function registerComplete(Request $request)
{
    $request->validate([
        'user_id'  => 'required|exists:users,id',
        'finger_id'=> 'required|integer'
    ]);

    // ربط البصمة بالمستخدم
    $user = User::find($request->user_id);
    $user->finger_id = $request->finger_id;
    $user->save();

    // إغلاق وضع التسجيل
    cache()->forget('finger_register');

    return response()->json([
        'ok' => true,
        'message' => 'Fingerprint registered successfully'
    ]);
}


    public function startEnroll(User $user)
{
    Cache::put('current_enroll_user_id', $user->id, now()->addMinutes(5));

    return response()->json([
        'ok' => true,
        'message' => 'Parmak kaydi baslatildi',
        'user' => $user->name
    ]);
}
    public function log(Request $request)
    {
        $request->validate([
            'finger_id' => 'required|integer'
        ]);

        // 🔍 البحث عن المستخدم عبر finger_id
        $user = User::where('finger_id', $request->finger_id)->first();

        // ❌ بصمة غير معروفة
        if (!$user) {
            return response()->json([
                'ok' => false,
                'message' => 'Taninmayan Parmak'
            ], 404);
        }

        // 🕒 تحديد دخول أو خروج
        $today = Carbon::today();

        $last = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->latest()
            ->first();

        $type = (!$last || $last->type === 'out') ? 'in' : 'out';

        // 📝 تسجيل الحضور
        Attendance::create([
            'user_id' => $user->id,
            'date'    => now()->toDateString(),
            'time'    => now()->toTimeString(),
            'type'    => $type,
        ]);

        return response()->json([
            'ok' => true,
            'user' => $user->name,
            'type' => $type
        ], 201);
    }
    
}