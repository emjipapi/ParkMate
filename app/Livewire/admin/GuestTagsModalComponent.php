<?php

namespace App\Livewire\Admin;

use App\Models\GuestTag;
use Livewire\Component;

class GuestTagsModalComponent extends Component
{
    public $guestTags;

    // Listen for an event to refresh the tag list
    protected $listeners = ['tagRegistered' => 'refreshTagList'];

    public function mount()
    {
        $this->refreshTagList();
    }

    public function refreshTagList()
    {
        // Fetch all guest tags, showing the newest ones first
        $this->guestTags = GuestTag::latest()->get();
    }
    
    public function render()
    {
        return view('livewire.admin.guest-tags-modal-component');
    }
}
