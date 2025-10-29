<?php
namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

class UsersComponent extends Component
{
    use WithPagination;

    public $activeTab;

    public function mount()
    {
        $permissions = json_decode(Auth::guard('admin')->user()->permissions ?? '[]', true);

        if (in_array('users_table', $permissions)) {
            $this->activeTab = 'users';
        } elseif (in_array('vehicles_table', $permissions)) {
            $this->activeTab = 'vehicles';
        } elseif (in_array('admins_table', $permissions)) {
            $this->activeTab = 'admins';
        } elseif (in_array('guests_table', $permissions)) {
            $this->activeTab = 'guests';
        } else {
            // fallback if user has none of the tabs
            $this->activeTab = null;
        }
    }

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function render()
    {
        return view('livewire.admin.users-component');
    }
}
