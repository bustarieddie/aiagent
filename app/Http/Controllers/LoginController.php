<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LoginController extends Controller {
    public function show() {
        if (session('admin_authed') === true) {
            return redirect()->route('admin.dashboard');
        }
        return view('login');
    }

    public function submit(Request $request) {
        $request->validate(['password' => 'required|string']);
        $expected = config('services.admin_password');

        if ($request->input('password') === $expected) {
            $request->session()->regenerate();
            $request->session()->put('admin_authed', true);
            return redirect()->route('admin.dashboard');
        }

        return back()->withErrors(['password' => 'Password salah.']);
    }

    public function logout(Request $request) {
        $request->session()->flush();
        $request->session()->regenerate();
        return redirect()->route('login');
    }
}
