<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use App\Models\Fingerprint;


class AdminController extends Controller
{
    public function showLogin()
    {
        return view('admin.login');
    }

  public function login(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'password' => 'required'
    ]);

    $admin = DB::table('admins')->where('email', $request->email)->first();

    if ($admin) {
        // 🔍 نتحقق من كلمة السر
        if (Hash::check($request->password, $admin->password)) {
            // ✅ نسجل الأدمن
            Session::put('admin_logged_in', true);
            Session::put('admin_id', $admin->id);
            Session::put('admin_email', $admin->email);

            return redirect('/dashboard')->with('success', 'Giriş başarılı!');
        } else {
            return back()->with('error', 'Şifre yanlış!');
        }
    }

    return back()->with('error', 'Email kayıtlı değil!');
}


public function dashboard()
{
    $users = \App\Models\User::all();
    $admins = \App\Models\Admin::all();
    $attendance = \App\Models\Attendance::all();
    $fingerprints = \App\Models\Fingerprint::with('user')->get(); // ✅ أضف هذا السطر

    return view('admin.dashboard', compact('users', 'admins', 'attendance', 'fingerprints'));
}




    public function logout()
    {
        Session::forget('admin_logged_in');
        return redirect('/login');
    }
}
