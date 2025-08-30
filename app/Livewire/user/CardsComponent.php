<?php

namespace App\Livewire\User;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CardsComponent extends Component
{
  public function render()
{
    $userId = Auth::id(); // get currently logged-in user's ID
    // Car slots (sensor-based)
    $totalCarSlots = DB::table('car_slots')->count();
    $totalCarOccupied = DB::table('car_slots')->where('occupied', 1)->count();

    // Motorcycle slots (counter-based, aggregated from all areas)
    $totalMotoSlots = DB::table('motorcycle_counts')->sum('total_available');
    $totalMotoAvailable = DB::table('motorcycle_counts')->sum('available_count');
    $totalMotoOccupied = $totalMotoSlots - $totalMotoAvailable;
    // Users
    $totalUsers = DB::table('users')->count();

        $recentActivities = DB::table('activity_logs')
            ->where('actor_type', 'user')       // only for users
            ->where('actor_id', $userId)       // current user's ID
            ->whereIn('action', ['login', 'logout', 'entry', 'exit']) // optional actions
            ->latest()
            ->take(5)
            ->get();

    return view('livewire.user.cards-component', [
        'totalCarSlots' => $totalCarSlots,
        'totalCarOccupied' => $totalCarOccupied,
        'totalMotoSlots' => $totalMotoSlots,
        'totalMotoOccupied' => $totalMotoOccupied,
        'totalUsers' => $totalUsers,
        'recentActivities' => $recentActivities,
    ]);
}

}
