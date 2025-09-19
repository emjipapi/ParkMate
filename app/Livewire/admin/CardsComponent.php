<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class CardsComponent extends Component
{
  public function render()
{
    // Car slots (sensor-based)
    $totalCarSlots = DB::table('car_slots')->count();
    $totalCarOccupied = DB::table('car_slots')->where('occupied', 1)->count();

    // Motorcycle slots (counter-based, aggregated from all areas)
    $totalMotoSlots = DB::table('motorcycle_counts')->sum('total_available');
    $totalMotoAvailable = DB::table('motorcycle_counts')->sum('available_count');
    $totalMotoOccupied = $totalMotoSlots - $totalMotoAvailable;

     // Users
        $totalUsers = DB::table('users')->count();

       // Activity Logs helpers
$today = Carbon::today();

// Entered / Exited Today (main gate only)
$entryCount = DB::table('activity_logs')
    ->whereDate('created_at', $today)
    ->where('action', 'entry')
    ->whereNull('area_id')
    ->count();

$exitCount = DB::table('activity_logs')
    ->whereDate('created_at', $today)
    ->where('action', 'exit')
    ->whereNull('area_id')
    ->count();

// Now compute "currently inside" using actor_type + actor_id (preferred)
$currentlyInside = 0;

// Today-only presence (last main-gate action TODAY is 'entry')
if (Schema::hasColumn('activity_logs', 'actor_id')) {
    $subToday = DB::table('activity_logs as s')
        ->select(DB::raw('MAX(s.id)'))
        ->whereDate('s.created_at', $today)
        ->where(function($q){
            $q->whereNull('s.area_id')->orWhere('s.area_id', 0);
        })
        ->whereNotNull('s.actor_id')
        ->where('s.actor_type', 'user')
        ->groupBy('s.actor_type', 's.actor_id');

    $currentlyInside = DB::table('activity_logs as al')
        ->where(function($q){
            $q->whereNull('al.area_id')->orWhere('al.area_id', 0);
        })
        ->whereIn('al.id', $subToday)
        ->where('al.action', 'entry')
        ->where('al.actor_type', 'user')
        ->count();
} else {
    $currentlyInside = max(0, $entryCount - $exitCount);
}


    // Activity Logs
    $recentActivities = DB::table('activity_logs')
        ->latest()
        ->take(4)
        ->get();

    return view('livewire.admin.cards-component', [
        'totalCarSlots' => $totalCarSlots,
        'totalCarOccupied' => $totalCarOccupied,
        'totalMotoSlots' => $totalMotoSlots,
        'totalMotoOccupied' => $totalMotoOccupied,
        'totalUsers' => $totalUsers,
        'recentActivities' => $recentActivities,
                    'entryCount' => $entryCount,
            'exitCount' => $exitCount,
            'currentlyInside' => $currentlyInside,
    ]);
}

}
