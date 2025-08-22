<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AdminAuthController;
use App\Http\Controllers\Auth\UserAuthController;
use App\Http\Controllers\ProfilePictureController;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Public / Login Routes
|--------------------------------------------------------------------------
*/

// User routes
Route::post('/login', [UserAuthController::class, 'login']);
Route::post('/logout', [UserAuthController::class, 'logout']);

// Admin routes
// Show login form
Route::get('/admin/login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login.form');

// Handle login POST
Route::post('/admin/login', [AdminAuthController::class, 'login'])->name('login');

Route::post('/admin/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

// Public pages (no authentication required)
Route::view('/parking-slots', 'parking-slots')->name('parking.slots');
Route::view('/users', 'users')->name('users');
Route::view('/users/create', 'user-create')->name('users.create');
Route::view('/sample', 'sample');
Route::view('/dashboard/live-attendance-mode', 'live-attendance-mode');
Route::view('/sticker-generator', 'sticker-generator');
Route::view('/violation-tracking', 'violation-tracking');
Route::view('/activity-log', 'activity-log');

Route::get('/profile-picture/{filename}', [ProfilePictureController::class, 'show'])
    ->name('profile.picture');

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
| Redirect users/admins to their dashboards if logged in, otherwise show login.
*/
// Route::get('/', function () {
//     if (Auth::guard('admin')->check()) {
//         return redirect()->route('admin.dashboard');
//     }

//     if (Auth::guard('web')->check()) {
//         return redirect()->route('user.dashboard');
//     }

//     return redirect()->route('login'); // show user login page to guests
// });
