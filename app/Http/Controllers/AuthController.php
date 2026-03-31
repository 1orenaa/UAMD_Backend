<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // Shfaq formen e loginit
    public function showLogin()
    {
        return view('auth.login');
    }

    // Proceson login
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($request->only('email', 'password'))) {
            $request->session()->regenerate();
            $role = Auth::user()->role;

            if ($role === 'pedagog') {
                return redirect()->route('pedagog.dashboard');
            }
            return redirect()->route('student.dashboard');
        }

        return back()->withErrors([
            'email' => 'Email ose fjalëkalimi është i gabuar.',
        ]);
    }

    // Shfaq formen e regjistrimit
    public function showRegister()
    {
        return view('auth.register');
    }

    // Proceson regjistrimin
    public function register(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users',
            'password' => 'required|min:6|confirmed',
            'role'     => 'required|in:student,pedagog',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => $request->role,
        ]);

        Auth::login($user);

        if ($user->role === 'pedagog') {
            return redirect()->route('pedagog.dashboard');
        }
        return redirect()->route('student.dashboard');
    }

    // Logout
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}