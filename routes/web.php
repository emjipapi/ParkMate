<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AdminAuthController;
use App\Http\Controllers\Auth\UserAuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProfilePictureController;
use App\Charts\AnalyticsChart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\EvidenceController;
/*
|--------------------------------------------------------------------------
| Public / Login Routes
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    // Logout both guards
    Auth::guard('admin')->logout();
    Auth::guard('web')->logout();

    // Optionally clear sessions
    session()->invalidate();
    session()->regenerateToken();

    return view('auth.welcome'); // selection page
})->name('login.selection');

// Show user login form
Route::get('/user/login', function (Request $request) {
    Auth::guard('web')->logout();   // logout user if logged in
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return app(UserAuthController::class)->showLoginForm();
})->name('user.login.form');
// Handle login
Route::post('/user/login', [UserAuthController::class, 'login'])->name('user.login.submit');
// Handle logout
Route::post('/user/logout', [UserAuthController::class, 'logout'])->name('user.logout');


// Show admin login form
Route::get('/admin/login', function (Request $request) {
    Auth::guard('admin')->logout();   // logout admin if logged in
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return app(AdminAuthController::class)->showLoginForm();
})->name('admin.login.form');

// Route::get('/', [AdminAuthController::class, 'showLoginForm'])
// ->name('admin.login.form');
// Handle login POST
Route::post('/admin/login', [AdminAuthController::class, 'login'])
    ->name('admin.login.submit');

Route::post('/admin/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

// Public pages (e.g., parking-slots) that anyone can see


/*
|--------------------------------------------------------------------------
| Protected Admin Routes (all pages require admin login)
|--------------------------------------------------------------------------
*/
Route::middleware(['admin'])->group(function () {
    // Dashboard
    Route::get('/admin-dashboard', function () {
        return view('admin.dashboard'); // admin dashboard
    })->name('admin.dashboard');

    // User management

    Route::get('/users/create-user', function () {
        return view('admin.user-create'); // Blade containing <livewire:user-form />
    })->name('users.create');

        Route::get('/users/create-admin', function () {
        return view('admin.admin-create'); // Blade containing <livewire:user-form />
    })->name('admins.create');

    Route::post('/users', [UserController::class, 'store'])->name('users.store');

    // Other admin pages
    // Route::view('/sample', 'sample');
    Route::view('/users', 'admin.users');
    Route::view('/admin-dashboard/live-attendance-mode', 'admin.live-attendance-mode');
    Route::view('/sticker-generator', 'admin.sticker-generator');
    Route::view('/violation-tracking', 'admin.violation-tracking');
    Route::view('/activity-log', 'admin.activity-log');
    Route::view('/parking-slots', 'admin.parking-slots')->name('parking.slots');
    // routes/web.php
    Route::get('/dashboard/analytics-dashboard', function () {
        $chart = new AnalyticsChart;
        return view('admin.analytics-dashboard', compact('chart'));
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
    Route::get('/user-dashboard', function () {
        return view('user.dashboard'); // normal user dashboard
    })->name('user.dashboard');
    Route::view('/user-parking-slots', 'user.parking-slots')->name('parking.slots');
    Route::view('/user-violation-tracking', 'user.violation-tracking')
    ->name('user.violation.tracking');
    Route::view('/user-settings', 'user.settings')->name('user.settings');
    Route::post('/evidence/upload', [EvidenceController::class, 'store'])->name('evidence.upload');
});
