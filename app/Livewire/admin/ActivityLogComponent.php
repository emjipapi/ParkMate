<?php
namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Violation;
use App\Models\Vehicle;
use App\Models\User;

class ActivityLogComponent extends Component
{
    public $activeTab = 'system';

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;

        // 🔔 tell child components to reset filters
        $this->dispatch('resetFilters');
    }

    public function render()
    {
        return view('livewire.admin.activity-log-component', [
        ]);
    }
}
