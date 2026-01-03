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

        // نخزّن أن في تسجيل بصمة شغّال
        Cache::put('fingerprint_register_user', $request->user_id, now()->addMinutes(5));

        return response()->json([
            'ok' => true,
            'message' => 'Parmak kaydı başlatıldı. Lütfen sensöre parmak koyun.'
        ]);
    }
}
