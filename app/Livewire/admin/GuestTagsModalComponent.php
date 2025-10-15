<?php

namespace App\Livewire\Admin;

use App\Models\GuestPass;
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
        $this->guestTags = GuestPass::latest()->get();
    }

    public function editTag($tagId)
    {
        $this->dispatch('loadTagForEdit', $tagId);
    }

    public function deleteTag($tagId)
    {
        GuestPass::find($tagId)?->delete();
        $this->refreshTagList();
    }
    
    public function render()
    {
        return view('livewire.admin.guest-tags-modal-component');
    }
}