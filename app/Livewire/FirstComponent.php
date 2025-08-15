<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\DB;

class FirstComponent extends Component
{
    public function render()
    {
        $totalSlots = DB::table('parking_slots')->count();
        $totalUsers = DB::table('users')->count();
        $totalStatus1 = DB::table('parking_slots')->where('status', 1)->count();

        return view('livewire.first-component', [
            'totalSlots' => $totalSlots,
            'totalUsers' => $totalUsers,
            'totalStatus1' => $totalStatus1,
        ]);
    }
    public function goTo($page)
    {
        return redirect()->to($page);
    }
}


