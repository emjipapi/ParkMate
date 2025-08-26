<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\DB;

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

    // Activity Logs
    $recentActivities = DB::table('activity_logs')
        ->latest()
        ->take(4)
        ->get();

    return view('livewire.cards-component', [
        'totalCarSlots' => $totalCarSlots,
        'totalCarOccupied' => $totalCarOccupied,
        'totalMotoSlots' => $totalMotoSlots,
        'totalMotoOccupied' => $totalMotoOccupied,
        'totalUsers' => $totalUsers,
        'recentActivities' => $recentActivities,
    ]);
}

}
