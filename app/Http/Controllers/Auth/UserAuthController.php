<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserAuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->only('student_id', 'employee_id', 'password');

        if (Auth::guard('web')->attempt($credentials)) {
            // ✅ Log activity
            activity_log('user', Auth::id(), 'login');
            return redirect()->intended('/dashboard');
        }

        return back()->withErrors(['error' => 'Invalid credentials']);
    }

    public function logout()
    {
        activity_log('user', Auth::id(), 'logout');
        Auth::guard('web')->logout();
        return redirect('/');
    }
}
