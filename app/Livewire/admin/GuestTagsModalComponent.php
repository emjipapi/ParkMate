<?php

namespace App\Livewire\Admin;

use App\Models\GuestTag;
use Livewire\Component;

class GuestTagsModalComponent extends Component
{
    public $guestTags;

    protected $listeners = ['tagRegistered' => 'refreshTagList'];

    public function mount()
    {
        $this->refreshTagList();
    }

    public function refreshTagList()
    {
        $this->guestTags = GuestTag::latest()->get();
    }

    public function editTag($tagId)
    {
        $this->dispatch('loadTagForEdit', $tagId);
    }

    public function deleteTag($tagId)
    {
        GuestTag::find($tagId)?->delete();
        $this->refreshTagList();
    }
    
    public function render()
    {
        return view('livewire.admin.guest-tags-modal-component');
    }
}