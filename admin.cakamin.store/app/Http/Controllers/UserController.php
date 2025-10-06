<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class UserController extends Controller
{
    public function settings()
    {
        $user = User::find(Auth::id());
        return Inertia::render('user/settings', ['user' => $user]);
    }
    public function logout()
    {
        Auth::logout();
        session()->flush();
        return redirect('/');
    }
    public function update_user_profile(Request $request)
    {
        $user = User::find(Auth::id());
        $array = $request->all();
        $user->update($array);
        return response()->json(['status' => true, 'message' => 'Berhasil memperbarui profil']);
    }
    public function update_user_password(Request $request)
    {
        $user = User::find(Auth::id());
        if (!password_verify($request->current_password, $user->password)) {
            return response()->json(['status' => false, 'message' => 'Password lama tidak sesuai']);
        }
        $user->update([
            'password' => bcrypt($request->new_password)
        ]);
        return response()->json(['status' => true, 'message' => 'Berhasil memperbarui password']);
    }
}
