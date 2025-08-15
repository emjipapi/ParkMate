<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // ✅ Needed for Auth::guard()



class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('login'); // create this view
    }

public function login(Request $request)
{
    // Validate input
    $request->validate([
        'username' => 'required',
        'password' => 'required',
    ]);

    $username = $request->input('username');
    $password = $request->input('password');

    // 1️⃣ Try admin first (admins use 'username')
    if (Auth::guard('admin')->attempt(['username' => $username, 'password' => $password])) {
        return redirect()->intended('/'); // admin dashboard (index.blade.php)
    }

    // 2️⃣ Try normal user (users use 'student_id')
    if (Auth::guard('web')->attempt(['student_id' => $username, 'password' => $password])) {
        return redirect()->intended('/userdashboard'); // user dashboard
    }

    // 3️⃣ Failed login
    return back()->withErrors(['username' => 'Invalid credentials']);
}




public function logout()
{
    Auth::guard('admin')->logout(); // if admin
    Auth::guard('web')->logout();   // if user
    return redirect('/login');       // redirect to login page
}

}
