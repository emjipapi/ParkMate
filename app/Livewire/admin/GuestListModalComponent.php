<?php

namespace App\Livewire\Admin;

use App\Models\GuestPass;
use Livewire\Component;

class GuestListModalComponent extends Component
{
    public $guests = [];

    // Listen for the event dispatched when a new guest is successfully registered
    protected $listeners = ['guestRegistered' => 'loadGuests'];

    public function mount()
    {
        $this->loadGuests();
    }

    /**
     * Fetches the list of currently active guests from the database.
     */
    public function loadGuests()
    {
        // We fetch guest passes that are currently in use, and eager load
        // the associated user and their vehicle information for efficiency.
        $this->guests = GuestPass::with(['user.vehicles'])
            ->where('status', 'in_use')
            ->latest('updated_at') // Sort by the most recent guest first
            ->get();
    }

    /**
     * Clears guest information and makes the tag available again
     */
    public function clearGuestInfo($guestPassId)
    {
        try {
            $guestPass = GuestPass::find($guestPassId);
            
            if (!$guestPass) {
                session()->flash('error', 'Guest pass not found.');
                return;
            }

            // Update the guest pass: set reason and user_id to null, status to available
            $guestPass->update([
                'reason' => null,
                'user_id' => null,
                'status' => 'available',
            ]);

            // Refresh the guest list
            $this->loadGuests();
            
            session()->flash('message', 'Guest information cleared successfully!');
        } catch (\Exception $e) {
            session()->flash('error', 'Error clearing guest information: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.admin.guest-list-modal-component');
    }
}