<?php

namespace App\Livewire\User;

use Livewire\Component;

class ViolationUserComponent extends Component
{
    public $activeTab = 'my_violations';

    // Search
    public $searchTerm = '';
    public $searchResults = [];

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function render()
    {
        return view('livewire.user.violation-user-component');
    }
}
