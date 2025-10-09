<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\User;

class UserInfoModal extends Component
{
    public $userId = null;
    public $user = null;
    public $loading = false;

    protected $listeners = [
        'openUserModal' => 'loadUser'
    ];

    public function loadUser($id)
    {
        $this->resetErrorBag();
        $this->user = null;
        $this->userId = (int) $id;
        $this->loading = true;

        // Eager-load relationships you need (vehicles here)
        $this->user = User::with(['vehicles'])->find($this->userId);

        $this->loading = false;

        if (! $this->user) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'User not found.']);
            return;
        }

        // Tell frontend to show modal
        $this->dispatch('show-user-modal');
    }

    public function render()
    {
        return view('livewire.admin.user-info-modal');
    }
}
