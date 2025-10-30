<?php

namespace App\Livewire\Admin;

use App\Models\GuestPass;
use App\Models\GuestRegistration;
use Livewire\Component;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

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
        // Fetch guest registrations where the guest pass is in_use
        // and eager load user and guest pass relationships
        $this->guests = GuestRegistration::whereHas('guestPass', function ($q) {
            $q->where('status', 'in_use');
        })
            ->with(['user', 'guestPass'])
            ->latest('updated_at') // Sort by the most recent guest first
            ->get();
    }

    /**
     * Clears guest information and makes the tag available again
     */
    public function clearGuestInfo($registrationId)
    {
        try {
            $registration = GuestRegistration::find($registrationId);
            
            if (!$registration) {
                session()->flash('error', 'Guest registration not found.');
                return;
            }

            $guestPass = $registration->guestPass;
            $user = $registration->user;

            // Soft delete the registration only
            $registration->delete();

            // Update the guest pass to available
            if ($guestPass) {
                $guestPass->update([
                    'user_id' => null,
                    'status' => 'available',
                ]);
            }

            ActivityLog::create([
                'actor_type' => 'admin',
                'actor_id'   => Auth::guard('admin')->id(),
                'action'     => 'update',
                'details'    => 'Admin ' . Auth::guard('admin')->user()->firstname . ' ' . Auth::guard('admin')->user()->lastname .
                                ' cleared guest registration for user "' . ($user->firstname ?? 'Unknown') . ' ' . ($user->lastname ?? '') .
                                '" with vehicle ' . ucfirst($registration->vehicle_type) . ' (' . $registration->license_plate . ')' .
                                ' and freed guest pass "' . ($guestPass->name ?? 'Unknown') . '".',
            ]);

            // Refresh the guest list
            $this->loadGuests();
            
            session()->flash('message', 'Guest checked out successfully!');
            
            // Close and reopen the modal
            $this->js('
                const modalEl = document.getElementById("guestListModal");
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
                setTimeout(() => {
                    const newModal = new bootstrap.Modal(modalEl);
                    newModal.show();
                }, 500);
            ');
            
        } catch (\Exception $e) {
            session()->flash('error', 'Error clearing guest information: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.admin.guest-list-modal-component');
    }
}