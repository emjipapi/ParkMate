<?php

use App\Charts\AnalyticsChart;
use App\Http\Controllers\Auth\AdminAuthController;
use App\Http\Controllers\Auth\UserAuthController;
use App\Http\Controllers\EvidenceController;
use App\Http\Controllers\ProfilePictureController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WebfontController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StickerDownloadController;
// new \App\Mail\ViolationThresholdReached;
use App\Models\ParkingMap;
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
// Route::get('/test-email/{userId}', function ($userId) {
//     try {
//         $user = \App\Models\User::find($userId);
//         if (! $user) {
//             return 'User not found';
//         }

//         \Illuminate\Support\Facades\Mail::to($user->email)
//             ->send(new ViolationThresholdReached($user));

//         return "Email sent to {$user->email}";
//     } catch (\Exception $e) {
//         return 'Error: '.$e->getMessage();
//     }
// });
Route::view('/live-attendance', 'live-attendance-mode');
// Profile pictures
Route::get('/profile-picture/{filename}', [ProfilePictureController::class, 'show'])
    ->name('profile.picture');

Route::post('/webfonts/add', [WebfontController::class, 'add'])->name('webfonts.add');

Route::get('/reports/attendance', [ReportController::class, 'generateAttendanceReport'])
    ->name('reports.attendance');

Route::get('/reports/endorsement', [ReportController::class, 'endorsementReport'])
    ->name('reports.endorsement');
Route::get('/stickers/download/{filename}', [StickerDownloadController::class, 'download'])
    ->name('stickers.download');
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
    Route::get('/users/edit/{id}', [UserController::class, 'edit'])->name('users.edit');
    // In your routes file
    Route::get('/admins/edit/{id}', [UserController::class, 'editAdmin'])->name('admins.edit');

    Route::post('/users', [UserController::class, 'store'])->name('users.store');

    // Other admin pages
    // Route::view('/sample', 'sample');
    Route::view('/users', 'admin.users');
    Route::view('/admin-dashboard/live-attendance-mode', 'admin.live-attendance-mode');
    Route::view('/sticker-generator', 'admin.sticker-generator');
    Route::view('/violation-tracking', 'admin.violation-tracking');
    Route::view('/create-report', 'admin.create-violation-report');
    Route::view('/activity-log', 'admin.activity-log');
    Route::view('/parking-slots', 'admin.parking-slots')->name('parking.slots');
    Route::view('/map-manager', 'admin.map-template-editor')->name('parking.slots');
    Route::get('/map/{map}', function (ParkingMap $map) {
    return view('admin.parking-map', ['map' => $map]);
})->name('parking-map.live');;
Route::get('/api/parking-map/{map}/statuses', function (ParkingMap $map) {
    $areaConfig = (array) ($map->area_config ?? []);
    $areaStatuses = [];
    
    foreach ($areaConfig as $areaKey => $cfg) {
        $enabled = !empty($cfg['enabled']);
        $parkingAreaId = $cfg['parking_area_id'] ?? null;
        
        $totalCarSlots = 0;
        $occupiedCarSlots = 0;
        $availableMotorcycleCount = null;
        
        if ($parkingAreaId) {
            $totalCarSlots = \App\Models\CarSlot::where('area_id', $parkingAreaId)->count();
            $occupiedCarSlots = \App\Models\CarSlot::where('area_id', $parkingAreaId)->where('occupied', 1)->count();
            
            $mc = \App\Models\MotorcycleCount::where('area_id', $parkingAreaId)->first();
            $availableMotorcycleCount = $mc?->available_count ?? null;
        }
        
        $availableCarSlots = max(0, $totalCarSlots - $occupiedCarSlots);
        
        // Determine state - exact same logic as Livewire component
        $state = 'unknown';
        if (!$enabled) {
            $state = 'disabled';
        } elseif ($totalCarSlots > 0 && $availableCarSlots > 0) {
            $state = 'available';
        } elseif ($totalCarSlots > 0 && $availableCarSlots === 0) {
            if ($availableMotorcycleCount === null) {
                $state = 'full';
            } elseif ((int)$availableMotorcycleCount > 0) {
                $state = 'moto_only';
            } else {
                $state = 'full';
            }
        } else {
            if ($availableMotorcycleCount !== null && (int)$availableMotorcycleCount > 0) {
                $state = 'available';
            } elseif ($availableMotorcycleCount === 0) {
                $state = 'full';
            } else {
                $state = 'unknown';
            }
        }
        
        $areaStatuses[$areaKey] = [
            'state' => $state,
            'total' => (int)$totalCarSlots,
            'occupied' => (int)$occupiedCarSlots,
            'available_cars' => (int)$availableCarSlots,
            'motorcycle_available' => $availableMotorcycleCount !== null ? (int)$availableMotorcycleCount : null,
        ];
    }
    
    return response()->json(['areaStatuses' => $areaStatuses]);
});
    // routes/web.php
    Route::get('/dashboard/analytics-dashboard', function () {
        $chart = new AnalyticsChart;

        return view('admin.analytics-dashboard', compact('chart'));
    });

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
    Route::view('/user-violation-tracking', 'user.violation-tracking');
    Route::view('/user-create-report', 'user.create-violation-report');
    Route::view('/user-settings', 'user.settings')->name('user.settings');
    // Route::post('/evidence/upload', [EvidenceController::class, 'store'])->name('evidence.upload');
});
