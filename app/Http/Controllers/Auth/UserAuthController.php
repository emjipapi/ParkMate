<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\StudentEmployee;

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

        $user = StudentEmployee::where('student_id', $login)
            ->orWhere('employee_id', $login)
            ->orWhere('email', $login)
            ->first();

        if ($user && \Hash::check($password, $user->password)) {
            Auth::login($user);
            activity_log('user', $user->id, 'login', 'User logged in');
            return redirect()->intended('/user-dashboard');
        }

        return back()->withErrors(['error' => 'Invalid credentials']);
    }

    public function logout()
    {
        if (Auth::check()) {
            activity_log('user', Auth::id(), 'logout', 'User logged out');
            Auth::logout();
        }
        return redirect()->route('login.selection');
    }
        public function showLoginForm()
    {
        return view('auth.student-login'); // make sure you have resources/views/admin/login.blade.php
    }
}
