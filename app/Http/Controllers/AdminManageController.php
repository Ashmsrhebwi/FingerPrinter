<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;

class AdminManageController extends Controller
{
    public function index()
    {
        $admins = Admin::all();
        return view('admin.dashboard', compact('admins'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:admins',
            'password' => 'required|min:4'
        ]);

        Admin::create([
            'email' => $request->email,
            'password' => Hash::make($request->password)
        ]);

        return redirect('/dashboard')->with('success', 'Yeni admin eklendi!');
    }

    public function update(Request $request, $id)
    {
        $admin = Admin::findOrFail($id);
        $request->validate([
            'email' => 'required|email|unique:admins,email,' . $id,
            'password' => 'nullable|min:4'
        ]);

        $admin->email = $request->email;
        if ($request->password) {
            $admin->password = Hash::make($request->password);
        }
        $admin->save();

        return redirect('/dashboard')->with('success', 'Admin bilgileri güncellendi!');
    }

    public function destroy($id)
    {
        $admin = Admin::findOrFail($id);
        $admin->delete();

        return redirect('/dashboard')->with('success', 'Admin silindi!');
    }
}
