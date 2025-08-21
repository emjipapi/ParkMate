<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Livewire\UserForm;
use App\Http\Controllers\ProfilePictureController;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Public / Login Routes
|--------------------------------------------------------------------------
*/

// Login page
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Public pages (no authentication required)
Route::get('/parking-slots', function () {
    return view('parking-slots');
})->name('parking.slots');

Route::get('/users', function () {
    return view('users');
})->name('users');

Route::get('/users/create', function () {
    return view('user-create');
})->name('users.create');

Route::get('/sample', function () {
    return view('sample');
});

Route::get('/dashboard/live-attendance-mode', function () {
    return view('live-attendance-mode');
});




Route::get('/profile-picture/{filename}', [ProfilePictureController::class, 'show'])
     ->name('profile.picture');

Route::get('/activity-log', function () {
    return view('activity-log');
});
/*
|--------------------------------------------------------------------------
| Protected Admin Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['admin'])->group(function () {
    Route::get('/dashboard', function () {
        return view('index'); // admin dashboard view
    })->name('admin.dashboard');
});

/*
|--------------------------------------------------------------------------
| Protected User Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    Route::get('/userdashboard', function () {
        return view('dashboard'); // normal user dashboard view
    })->name('user.dashboard');
});

/*
|--------------------------------------------------------------------------
| Root Route
|--------------------------------------------------------------------------
|
| Redirect users/admins to their dashboards if logged in, otherwise show login.
|
*/
Route::get('/', function () {
    if (Auth::guard('admin')->check()) {
        return redirect()->route('admin.dashboard');
    }

    if (Auth::guard('web')->check()) {
        return redirect()->route('user.dashboard');
    }

    return redirect()->route('login'); // show login page to guests
});
