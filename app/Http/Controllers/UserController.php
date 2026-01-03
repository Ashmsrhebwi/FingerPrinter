<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Fingerprint; // ✅ تأكد من استدعاء الموديل

class UserController extends Controller
{
    // ✅ عرض المستخدمين
    public function index()
    {
        $users = User::all();
        $fingerprints = Fingerprint::with('user')->get(); // عرض البصمات أيضاً
        return view('admin.dashboard', compact('users', 'fingerprints'));
    }

    // ✅ إضافة مستخدم جديد
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'dept' => 'nullable|string|max:255',
            'unit' => 'nullable|string|max:255',
            'status' => 'required|in:Aktif,Pasif',
        ]);

        // أولاً: إنشاء المستخدم
        $user = User::create([
            'name' => $request->name,
            'dept' => $request->dept,
            'unit' => $request->unit,
            'status' => $request->status,
        ]);

        // ثانياً: حفظ ملف البصمة إن وُجد
        if ($request->hasFile('finger_data')) {
            $fingerFile = $request->file('finger_data');
            $fingerContent = base64_encode(file_get_contents($fingerFile));

            Fingerprint::create([
                'user_id' => $user->id,
                'finger_name' => 'Right Thumb',
                'finger_data' => $fingerContent
            ]);
        }

        return redirect('/dashboard')->with('success', 'Yeni kullanıcı eklendi!');
    }

    // ✅ تعديل المستخدم
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'dept' => 'nullable|string|max:255',
            'unit' => 'nullable|string|max:255',
            'status' => 'required|in:Aktif,Pasif',
        ]);

        $user = User::findOrFail($id);
        $user->update($request->all());

        return redirect('/dashboard')->with('success', 'Kullanıcı başarıyla güncellendi!');
    }

    // ✅ حذف المستخدم
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return redirect('/dashboard')->with('success', 'Kullanıcı başarıyla silindi!');
    }
}
