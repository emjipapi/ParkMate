<?php
namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;

class UsersTable extends Component
{
    use WithPagination;

    public $search = '';

    public function updatingSearch()
    {
        $this->resetPage(); // so pagination resets when you type
    }

    public function render()
    {
        $users = User::query()
            ->where('firstname', 'like', "%{$this->search}%")
            ->orWhere('lastname', 'like', "%{$this->search}%")
            ->orWhere('middlename', 'like', "%{$this->search}%")
            ->orWhere('program', 'like', "%{$this->search}%")
            ->orWhere('department', 'like', "%{$this->search}%")
            ->paginate(10);

        return view('livewire.users-table', [
            'users' => $users,
        ]);
    }
}

