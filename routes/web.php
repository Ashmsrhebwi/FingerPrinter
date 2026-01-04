<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AdminManageController;
use App\Http\Controllers\FingerprintController;
use App\Http\Controllers\FingerprintAdminController;
use Illuminate\Support\Facades\Cache;


// ✅ توجيه الصفحة الرئيسية إلى /login
Route::get('/', fn() => redirect('/login'));

// ✅ تسجيل الدخول / الخروج
Route::get('/login', [AdminController::class, 'showLogin'])->name('login');
Route::post('/login', [AdminController::class, 'login']);
Route::get('/logout', [AdminController::class, 'logout']);

// ✅ لوحة التحكم (dashboard)
Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');


Route::post('/fingerprint/start-enroll/{user}', [FingerprintController::class, 'startEnroll']);
Route::get('/fingerprint/current-enroll', [FingerprintController::class, 'currentEnroll']);
Route::post('/fingerprint/register-complete', [FingerprintController::class, 'registerComplete']);
Route::post('/fingerprint/start-register', [FingerprintController::class, 'startRegister']);

Route::get('/fingerprint/status', function () {
    $register = cache()->get('finger_register', false);
    $userId = cache()->get('finger_user_id');

    return response()->json([
        'register' => $register ? 1 : 0,
        'user_id'  => (int)$userId
    ]);
});


Route::get('/fingerprint/check', function () {
    if (!Cache::has('finger_register')) {
        return response()->json([
            'register' => false
        ]);
    }

    return response()->json([
        'register' => true,
        'user_id' => Cache::get('finger_register')
    ]);
});



// ✅ المستخدمون (CRUD)
Route::post('/users', [UserController::class, 'store'])->name('users.store');
Route::put('/users/{id}', [UserController::class, 'update'])->name('users.update');
Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('users.destroy');

// ✅ الحضور Attendance
Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');

Route::post('/fingerprint/log', [FingerprintController::class, 'log']);

Route::post(
  '/fingerprint/start-register',
  [FingerprintAdminController::class, 'start']
);


// ✅ إدارة الأدمنات Admin Management (CRUD)
Route::post('/admins', [AdminManageController::class, 'store'])->name('admins.store');
Route::put('/admins/{id}', [AdminManageController::class, 'update'])->name('admins.update');
Route::delete('/admins/{id}', [AdminManageController::class, 'destroy'])->name('admins.destroy');
Route::get('/admins', [AdminManageController::class, 'index'])->name('admins.index');

Route::post('/admins', [AdminManageController::class, 'store'])->name('admins.store');
Route::put('/admins/{id}', [AdminManageController::class, 'update'])->name('admins.update');
Route::delete('/admins/{id}', [AdminManageController::class, 'destroy'])->name('admins.destroy');

Route::get('/attendance/{id}', [AttendanceController::class, 'show'])->name('attendance.show');
