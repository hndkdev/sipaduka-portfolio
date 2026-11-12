<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            // Redirect berdasarkan role
            if (auth()->user()->isAdmin()) {
                return redirect()->route('admin.dashboard');
            } elseif (auth()->user()->isRW()) {
                return redirect()->route('rw.dashboard');
            }

            return redirect()->route('keluarga-appscript.index');
        }

        return back()->withErrors([
            'username' => 'Username atau password salah',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    // Di App\Http\Controllers\AuthController.php
protected function authenticated()
{
    $user = auth()->user();
    
    if ($user->role === 'admin') {
        return redirect()->route('admin.dashboard');
    } elseif ($user->role === 'rw') {
        return redirect()->route('rw.dashboard');
    }
    
    return redirect()->route('login');
}
}

