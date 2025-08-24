<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\DB;

class CardsComponent extends Component
{
    public function render()
    {
        $totalSlots = DB::table('parking_slots')->count();
        $totalUsers = DB::table('users')->count();
        $totalStatus1 = DB::table('parking_slots')->where('status', 1)->count();

        // Fetch latest 3 activity logs
        $recentActivities = DB::table('activity_logs')
            ->latest()
            ->take(6)
            ->get();

        return view('livewire.cards-component', [
            'totalSlots' => $totalSlots,
            'totalUsers' => $totalUsers,
            'totalStatus1' => $totalStatus1,
            'recentActivities' => $recentActivities,
        ]);
    }

    public function goTo($page)
    {
        return redirect()->to($page);
    }
}
