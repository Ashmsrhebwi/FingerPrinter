<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class FingerprintAdminController extends Controller
{
    public function start(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer'
        ]);

        // نخزّن أن في تسجيل بصمة شغّال بالمفاتيح الصحيحة التي يبحث عنها ESP32
        Cache::put('finger_register', true, now()->addMinutes(5));
        Cache::put('finger_user_id', $request->user_id, now()->addMinutes(5));

        return response()->json([
            'ok' => true,
            'message' => 'Parmak kaydı başlatıldı. Lütfen sensöre parmak koyun.'
        ]);
    }
}
