<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class RegisterTagModalComponent extends Component
{
    public $tagName = '';
    public $tagId = '';

    // Remove mount() - it only runs once on page load
    // Instead, reset values when modal opens
    
    #[\Livewire\Attributes\On('refreshComponent')]
    public function refreshComponent()
    {
        // This runs when modal opens
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->tagName = '';
        $this->tagId = '';
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
        $this->validate([
            'tagName' => 'required|string|max:255',
            'tagId' => 'required|string|max:255',
        ]);

        // Logic to save the new tag would go here.
        // For example: GuestTag::create([...]);

        // Close modal and show success
        $this->dispatch('close-register-tag-modal');
        session()->flash('message', 'Tag registered successfully!');
    }

    public function render()
    {
        return view('livewire.admin.register-tag-modal-component');
    }
}