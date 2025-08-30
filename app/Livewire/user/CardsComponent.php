<?php

namespace App\Livewire\User;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CardsComponent extends Component
{
public function render()
{
    $userId = Auth::id();

    // Car slots
    $totalCarSlots = DB::table('car_slots')->count();
    $totalCarOccupied = DB::table('car_slots')->where('occupied', 1)->count();

    // Motorcycle slots
    $totalMotoSlots = DB::table('motorcycle_counts')->sum('total_available');
    $totalMotoAvailable = DB::table('motorcycle_counts')->sum('available_count');
    $totalMotoOccupied = $totalMotoSlots - $totalMotoAvailable;

    // User-specific counts
    $myViolationsCount = DB::table('violations')
        ->where('violator_id', $userId)
        ->count();

    $myPendingReports = DB::table('violations')
        ->where('reporter_id', $userId)
        ->where('status', 'pending')
        ->count();

    // Recent Activity
    $recentActivities = DB::table('activity_logs')
        ->where('actor_type', 'user')
        ->where('actor_id', $userId)
        ->latest()
        ->take(5)
        ->get();

    return view('livewire.user.cards-component', [
        'totalCarSlots' => $totalCarSlots,
        'totalCarOccupied' => $totalCarOccupied,
        'totalMotoSlots' => $totalMotoSlots,
        'totalMotoOccupied' => $totalMotoOccupied,
        'myViolationsCount' => $myViolationsCount,
        'myPendingReports' => $myPendingReports,
        'recentActivities' => $recentActivities,
    ]);
}

}
