<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function home()
    {
        return Auth::check() ? redirect()->route('dashboard') : redirect()->route('login');
    }

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Invalid email or password.'])->onlyInput('email');
        }

        if (Auth::user()->status !== 'active') {
            Auth::logout();

            return back()->withErrors(['email' => 'This user account is inactive.'])->onlyInput('email');
        }

        $request->session()->regenerate();
        ActivityLogger::log(Auth::id(), 'LOGIN', Auth::user()->name . ' logged in');

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
