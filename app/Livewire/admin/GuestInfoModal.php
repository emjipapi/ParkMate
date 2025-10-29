<?php
namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\User;
use App\Models\GuestPass;

class GuestInfoModal extends Component
{
    public $guest = null;
    public $loading = false;
    public $guestPass = null;

    #[\Livewire\Attributes\On('openGuestModal')]
    public function openModal($id)
    {
        $this->loading = true;
        // Use withTrashed() to find both active and deleted guests, and eager load vehicles with trashed
        $this->guest = User::withTrashed()
            ->with(['vehicles' => function($query) {
                $query->withTrashed();
            }])
            ->find($id);
        
        // Find the guest pass by matching the RFID tag from the guest's vehicle (including soft-deleted)
        if ($this->guest && $this->guest->vehicles->count() > 0) {
            $vehicle = $this->guest->vehicles->first();
            if ($vehicle) {
                $this->guestPass = GuestPass::where('rfid_tag', $vehicle->rfid_tag)->first();
            }
        }
        
        $this->loading = false;
        
        // Dispatch the JavaScript event to show the modal
        $this->js('
            const modalEl = document.getElementById("guestInfoModal");
            if (modalEl) {
                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.show();
            }
        ');
    }

    public function render()
    {
        return view('livewire.admin.guest-info-modal');
    }
}
