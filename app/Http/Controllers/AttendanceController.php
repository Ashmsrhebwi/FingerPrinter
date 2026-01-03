<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Attendance;

class AttendanceController extends Controller
{
    // الصفحة الرئيسية (Dashboard)
    public function index()
    {
        $attendance = Attendance::with('user')
            ->orderBy('date', 'desc')
            ->get();

        $users = User::all();

        return view('admin.dashboard', compact('attendance', 'users'));
    }

    // صفحة تفاصيل مستخدم واحد
    public function show($id)
    {
        $user = User::findOrFail($id);

        $records = Attendance::where('user_id', $id)
            ->orderBy('date', 'desc')
            ->get();

        return view('admin.attendance-show', compact('user', 'records'));
    }
}