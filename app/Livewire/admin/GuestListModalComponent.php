<?php

namespace App\Livewire\Admin;

use App\Models\GuestPass;
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

            $user = $guestPass->user;

            // Soft delete the user's vehicles
            if ($user && $user->vehicles) {
                $user->vehicles()->delete();
            }

            // Soft delete the user
            if ($user) {
                $user->delete();
            }

            // Update the guest pass
            $guestPass->update([
                'reason' => null,
                'user_id' => null,
                'status' => 'available',
            ]);
            ActivityLog::create([
    'actor_type' => 'admin',
    'actor_id'   => Auth::guard('admin')->id(),
    'action'     => 'modify',
    'details'    => 'Admin ' . Auth::guard('admin')->user()->firstname . ' ' . Auth::guard('admin')->user()->lastname .
                    ' cleared guest information for user "' . $user->firstname . ' ' . $user->lastname .
                    '" and freed guest pass "' . $guestPass->name . '".',
]);

            // Refresh the guest list
            $this->loadGuests();
            
            session()->flash('message', 'Guest information cleared successfully!');
            
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