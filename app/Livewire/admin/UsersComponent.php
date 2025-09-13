<?php
namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Violation;
use App\Models\Vehicle;
use App\Models\User;


class UsersComponent extends Component
{
    use WithPagination;
    public $activeTab = 'users';
    protected $queryString = [];
    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;

        // 🔔 tell child components to reset filters
        // $this->resetPage();
        // $this->dispatch('resetFilters');
    }

    public function render()
    {
        return view('livewire.admin.users-component', [
        ]);
    }
}
