<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserAuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        $login = $request->login;
        $password = $request->password;

        $user = User::where('student_id', $login)
            ->orWhere('employee_id', $login)
            ->orWhere('email', $login)
            ->first();

        if ($user && \Hash::check($password, $user->password)) {
            Auth::login($user);
            $fullName = "{$user->lastname}, {$user->firstname}";
            activity_log('user', $user->id, 'login', "User '{$fullName}' logged in");

            return redirect()->intended('/user-dashboard');
        }

        return back()->withErrors(['error' => 'Invalid credentials']);
    }

    public function logout()
    {
        if (Auth::check()) {
            $user = Auth::user();
            $fullName = "{$user->lastname}, {$user->firstname}";

            // ✅ Include full name in log
            activity_log('user', $user->id, 'logout', "User '{$fullName}' logged out");
            Auth::logout();
        }

        return redirect()->route('login.selection');
    }

    public function showLoginForm()
    {
        return view('auth.user-login'); // make sure you have resources/views/admin/login.blade.php
    }
}
