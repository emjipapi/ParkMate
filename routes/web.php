<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AdminAuthController;
use App\Http\Controllers\Auth\UserAuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProfilePictureController;
use \App\Http\Livewire\UserForm;
use App\Http\Controllers\AnalyticsController;
use App\Charts\AnalyticsChart;

/*
|--------------------------------------------------------------------------
| Public / Login Routes
|--------------------------------------------------------------------------
*/

// User login/logout
Route::post('/login', [UserAuthController::class, 'login']);
Route::post('/logout', [UserAuthController::class, 'logout']);

// Show admin login form
Route::get('/admin/login', [AdminAuthController::class, 'showLoginForm'])
    ->name('admin.login.form');

// Handle login POST
Route::post('/admin/login', [AdminAuthController::class, 'login'])
    ->name('admin.login.submit');

Route::post('/admin/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

// Public pages (e.g., parking-slots) that anyone can see
Route::view('/parking-slots', 'parking-slots')->name('parking.slots');

/*
|--------------------------------------------------------------------------
| Protected Admin Routes (all pages require admin login)
|--------------------------------------------------------------------------
*/
Route::middleware(['admin'])->group(function () {
    // Dashboard
    Route::get('/dashboard', function () {
        return view('index'); // admin dashboard
    })->name('admin.dashboard');

    // User management
    Route::view('/users', 'users')->name('users');
        Route::get('/users/create', function () {
        return view('user-create'); // Blade containing <livewire:user-form />
    })->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');

    // Other admin pages
    Route::view('/sample', 'sample');
    Route::view('/dashboard/live-attendance-mode', 'live-attendance-mode');
    Route::view('/sticker-generator', 'sticker-generator');
    Route::view('/violation-tracking', 'violation-tracking');
    Route::view('/activity-log', 'activity-log');
    // routes/web.php
Route::get('/dashboard/analytics-dashboard', function () {
    $chart = new AnalyticsChart;
    return view('analytics-dashboard', compact('chart'));
});


    // Profile pictures
    Route::get('/profile-picture/{filename}', [ProfilePictureController::class, 'show'])
        ->name('profile.picture');
});
// Route::get('/analytics', [AnalyticsController::class, 'index']);



/*
|--------------------------------------------------------------------------
| Protected User Routes (normal user login)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    Route::get('/userdashboard', function () {
        return view('dashboard'); // normal user dashboard
    })->name('user.dashboard');
});
