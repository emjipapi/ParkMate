<?php
namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\GuestRegistration;

class GuestInfoModal extends Component
{
    public $registration = null;
    public $guest = null;
    public $loading = false;

    #[\Livewire\Attributes\On('openGuestModal')]
    public function openModal($id)
    {
        $this->loading = true;
        // Fetch the registration with its relationships
        $this->registration = GuestRegistration::withTrashed()
            ->with(['user' => function($query) {
                $query->withTrashed();
            }, 'guestPass'])
            ->find($id);
        
        // Set guest to the user for easy access in blade
        if ($this->registration) {
            $this->guest = $this->registration->user;
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
