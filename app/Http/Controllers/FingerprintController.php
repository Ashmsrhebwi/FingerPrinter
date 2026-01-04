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

        // حفظ في الـ cache لمدة 5 دقائق
        cache()->put('finger_register', true, now()->addMinutes(5));
        cache()->put('finger_user_id', $request->user_id, now()->addMinutes(5));

        return response()->json([
            'ok' => true,
            'message' => 'Parmak kaydı başlatıldı. Lütfen sensöre parmak koyun.'
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
    cache()->forget('finger_user_id');

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

        // 🛑 التحقق من حالة المستخدم (Pasif)
        if ($user->status === 'Pasif') {
            return response()->json([
                'ok' => false,
                'message' => 'Account Deactivated',
                'user' => $user->name
            ], 403);
        }

        // 🕒 تحديد دخول أو خروج
        $today = Carbon::today()->toDateString();

        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        if (!$attendance) {
            // أول بصمة في اليوم -> تسجيل دخول
            Attendance::create([
                'user_id' => $user->id,
                'date'    => $today,
                'in_time' => now()->toTimeString(),
                'status'  => 'Normal',
            ]);
            $type = 'in';
            $message = "Welcome {$user->name}!";
        } else {
            // يوجد سجل سابق
            if ($attendance->out_time) {
                // إذا كان مسجل خروج مسبقاً، نمنع التكرار إلا لو مر 5 دقائق مثلاً (للأمان)
                $lastOut = Carbon::parse($attendance->out_time);
                if (now()->diffInMinutes($lastOut) < 5) {
                    return response()->json([
                        'ok'      => true, // نرسل true عشان الأردوينو ما يعطي Warning
                        'message' => "Already Out, {$user->name}!",
                        'user'    => $user->name,
                        'type'    => 'none' 
                    ]);
                }
            }
            
            // تسجيل خروج أو تحديث الخروج
            $attendance->update([
                'out_time' => now()->toTimeString(),
            ]);
            $type = 'out';
            $message = "Good Bye {$user->name}!";
        }

        return response()->json([
            'ok'      => true,
            'message' => $message,
            'user'    => $user->name,
            'type'    => $type
        ], 201);
    }
    
}