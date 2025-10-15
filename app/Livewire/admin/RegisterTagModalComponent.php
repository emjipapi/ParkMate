<?php

namespace App\Livewire\Admin;

use App\Models\GuestTag;
use Livewire\Component;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;

class RegisterTagModalComponent extends Component
{
    public $tagName = '';
    public $tagId = '';
    public $editingTagId = null;
    public $isEditMode = false;

    #[\Livewire\Attributes\On('loadTagForEdit')]
    public function loadTagForEdit($tagId)
    {
        $tag = GuestTag::find($tagId);
        if ($tag) {
            $this->editingTagId = $tag->id;
            $this->tagName = $tag->name;
            $this->tagId = $tag->rfid_tag;
            $this->isEditMode = true;
        }
    }

    #[\Livewire\Attributes\On('refreshComponent')]
    public function refreshComponent()
    {
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->tagName = '';
        $this->tagId = '';
        $this->editingTagId = null;
        $this->isEditMode = false;
        $this->resetErrorBag();
    }

    public function scanRfid()
    {
        try {
            $this->resetErrorBag('tagId');

            $response = Http::timeout(15)->get('http://192.168.1.199:5001/wait-for-scan');

            if ($response->successful()) {
                $data = $response->json();

                if ($data['success'] && isset($data['rfid_tag'])) {
                    $this->tagId = $data['rfid_tag'];
                } else {
                    $this->addError("tagId", $data['error'] ?? 'No RFID scan received from the device.');
                }
            } else {
                $this->addError("tagId", 'Failed to connect to the RFID scanner.');
            }
        } catch (\Exception $e) {
            $this->addError("tagId", 'RFID scanner is not running or the connection timed out.');
        }
    }

    public function saveTag()
    {
        if ($this->isEditMode) {
            $this->updateTag();
        } else {
            $this->createTag();
        }
    }

    public function createTag()
    {
        $this->validate([
            'tagName' => 'required|string|max:255',
            'tagId' => 'required|string|max:255|unique:guest_tags,rfid_tag',
        ]);

        GuestTag::create([
            'name' => $this->tagName,
            'rfid_tag' => $this->tagId,
        ]);

        $this->dispatch('close-register-tag-modal');
        $this->dispatch('tagRegistered'); // Notify GuestTagsModalComponent to refresh
        session()->flash('message', 'Tag registered successfully!');
        $this->resetForm();
    }

    public function updateTag()
    {
        $tag = GuestTag::find($this->editingTagId);
        if (!$tag) {
            $this->addError('tagId', 'Tag not found');
            return;
        }

        $this->validate([
            'tagName' => 'required|string|max:255',
            'tagId' => [
                'required',
                'string',
                'max:255',
                Rule::unique('guest_tags', 'rfid_tag')->ignore($tag->id),
            ],
        ]);

        $tag->update([
            'name' => $this->tagName,
            'rfid_tag' => $this->tagId,
        ]);

        $this->dispatch('close-register-tag-modal');
        $this->dispatch('tagRegistered'); // Notify GuestTagsModalComponent to refresh
        session()->flash('message', 'Tag updated successfully!');
        $this->resetForm();
    }

    public function render()
    {
        return view('livewire.admin.register-tag-modal-component');
    }
}