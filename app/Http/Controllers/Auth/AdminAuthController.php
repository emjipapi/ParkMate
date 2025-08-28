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
        // Regenerate session to prevent fixation attacks
        $request->session()->regenerate();
        
        $adminId = Auth::guard('admin')->id();
        activity_log('admin', $adminId, 'login', 'Admin logged in');

        // Store admin ID in session
        session(['admin_id' => $adminId]);

        return redirect()->intended('/admin-dashboard');
    }

    return back()->withErrors(['error' => 'Invalid credentials']);
}

public function logout()
{
    if (Auth::guard('admin')->check()) {
        activity_log('admin', Auth::guard('admin')->id(), 'logout', 'Admin logged out');
        Auth::guard('admin')->logout();
        
        // Clear the session
        request()->session()->invalidate();
        request()->session()->regenerateToken();
    }

    return redirect()->route('login.selection');
}

public function showLoginForm()
{
    return view('auth.admin-login');
}
}