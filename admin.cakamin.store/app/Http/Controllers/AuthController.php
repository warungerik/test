<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AuthController extends Controller
{
    public function login()
    {
        return Inertia::render('auth/login');
    }

    public function proses_login(Request $request)
    {
        $user = User::where('username', $request->username)->first();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Username atau password salah!']);
        }
        $remember = $request->remember;
        if (Auth::attempt(['username' => $request->username, 'password' => $request->password], $remember)) {
            return response()->json(['status' => true, 'message' => 'Login berhasil!']);
        } else {
            return response()->json(['status' => false, 'message' => 'Username atau password salah!']);
        }
    }
}
