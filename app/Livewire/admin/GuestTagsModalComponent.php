<?php

namespace App\Livewire\Admin;

use App\Models\GuestPass;
use Livewire\Component;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

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
    $tag = GuestPass::find($tagId);

    if (!$tag) {
        session()->flash('error', 'Guest tag not found.');
        return;
    }

    $tagName = $tag->name;
    $rfid = $tag->rfid_tag;

    // Soft delete the tag
    $tag->delete();

    // Log the activity
    ActivityLog::create([
        'actor_type' => 'admin',
        'actor_id'   => Auth::guard('admin')->id(),
        'action'     => 'delete',
        'details'    => 'Admin ' . Auth::guard('admin')->user()->firstname . ' ' . Auth::guard('admin')->user()->lastname .
                        ' deleted guest tag "' . $tagName . '" (RFID: ' . $rfid . ').',
    ]);

    $this->refreshTagList();
                $this->js('
                const modalEl = document.getElementById("guestTagsModal");
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
                setTimeout(() => {
                    const newModal = new bootstrap.Modal(modalEl);
                    newModal.show();
                }, 500);
            ');
    session()->flash('message', 'Guest tag deleted successfully!');
}

    
    public function render()
    {
        return view('livewire.admin.guest-tags-modal-component');
    }
}