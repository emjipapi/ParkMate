<?php
namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Violation;
use App\Models\Vehicle;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ActivityLogComponent extends Component
{
    use WithPagination;
    public $activeTab = 'system';
        // 👇 sync activeTab with query string
    protected $queryString = [
        'activeTab' => ['except' => 'system'], 
    ];
        public function mount()
    {
        $permissions = json_decode(Auth::guard('admin')->user()->permissions ?? '[]', true);

        if (in_array('system_logs', $permissions)) {
            $this->activeTab = 'system';
        } elseif (in_array('entry_exit_logs', $permissions)) {
            $this->activeTab = 'entry/exit';
        } elseif (in_array('unknown_tags', $permissions)) {
            $this->activeTab = 'unknown';
        } else {
            // fallback if user has none of the three
            $this->activeTab = null;
        }
    }
    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;

        // 🔔 tell child components to reset filters
        $this->resetPage();
        $this->dispatch('resetFilters');
        $this->dispatch('clear-query-string');
    }

    public function render()
    {
        return view('livewire.admin.activity-log-component', [
        ]);
    }
}
