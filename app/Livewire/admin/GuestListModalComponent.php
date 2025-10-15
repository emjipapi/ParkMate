<?php

namespace App\Livewire\Admin;

use Livewire\Component;

class GuestListModalComponent extends Component
{
    public $guests = [];

    public function mount()
    {
        // This is a placeholder. You'll want to replace this with a
        // database query to fetch your actual guest list.
        $this->guests = [
            ['name' => 'John Doe', 'plate_number' => 'BEE 123', 'time_in' => '10:15 AM', 'location' => 'Area 51'],
            ['name' => 'Jane Smith', 'plate_number' => 'CAR 456', 'time_in' => '10:20 AM', 'location' => 'Main Parking'],
            ['name' => 'Peter Jones', 'plate_number' => 'TRK 789', 'time_in' => '10:32 AM', 'location' => 'Area 51'],
        ];
    }

    public function render()
    {
        return view('livewire.admin.guest-list-modal-component');
    }
}
