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

    $adminId = Auth::guard('admin')->id(); // get logged-in admin ID
    activity_log('admin', $adminId, 'login', 'Admin logged in');

    session(['admin_id' => $adminId]); // store admin ID in session for Livewire

    return redirect()->intended('/dashboard');
}


    return back()->withErrors(['error' => 'Invalid credentials']);
}

public function logout()
{
    if (Auth::guard('admin')->check()) {
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
