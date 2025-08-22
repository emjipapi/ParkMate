<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuthController extends Controller
{
public function login(Request $request)
{
    $credentials = $request->only('username', 'password');

    if (Auth::guard('admin')->attempt($credentials)) {
        // ❌ This is wrong: activity_log('admin', 'admin', 'login', 'Admin logged in');
        // ✅ Correct:
        activity_log('admin', Auth::guard('admin')->id(), 'login', 'Admin logged in');

        return redirect()->intended('/dashboard');
    }

    return back()->withErrors(['error' => 'Invalid credentials']);
}

public function logout()
{
    if (Auth::guard('admin')->check()) {
        // ❌ Wrong: activity_log('admin', 'admin', 'logout', 'Admin logged out');
        // ✅ Correct:
        activity_log('admin', Auth::guard('admin')->id(), 'logout', 'Admin logged out');

        Auth::guard('admin')->logout();
    }

    return redirect()->route('admin.login.form');
}


    public function showLoginForm()
    {
        return view('login'); // make sure you have resources/views/admin/login.blade.php
    }
}
