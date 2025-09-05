<?php
namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Violation;
use App\Models\Vehicle;
use App\Models\User;

class ViolationAdminComponent extends Component
{
    public $activeTab = 'pending';

    // Search
    public $searchTerm = '';
    public $searchResults = [];

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function updatedSearchTerm()
    {
        if (strlen($this->searchTerm) >= 2) {
            $this->searchResults = Vehicle::where('user_id', 'like', '%'.$this->searchTerm.'%')
                ->orWhere('license_plate', 'like', '%'.$this->searchTerm.'%')
                ->limit(10)
                ->get();
        } else {
            $this->searchResults = [];
        }
    }

    public function selectResult($vehicleId)
    {
        $vehicle = Vehicle::find($vehicleId);
        $this->searchResults = [];
        $this->searchTerm = $vehicle->license_plate ?? '';
        // Optionally: emit event to children so they filter by this vehicle
        $this->dispatch('filterByVehicle', vehicleId: $vehicleId);
    }

    public function render()
    {
        return view('livewire.admin.violation-admin-component', [
            'searchResults' => $this->searchResults,
        ]);
    }
}
