<?php

namespace App\Livewire\Admin;

use App\Models\User;
use App\Models\Vehicle;
use App\Models\GuestPass;
use Livewire\Component;

class RegisterGuestModalComponent extends Component
{
    // User fields
    public $firstname = '';
    public $middlename = '';
    public $lastname = '';
    public $contactNumber = '';

    // Vehicle fields
    public $licensePlate = '';
    public $vehicleType = ''; // Added vehicle type

    // Guest fields
    public $reason = '';
    public $selectedTag = '';

    // Dropdowns data
    public $guestTags = [];
    public $reasons = [
        'delivery' => 'Package Delivery',
        'service' => 'Service/Maintenance',
        'contractor' => 'Contractor/Service Provider',
        'visitor' => 'Student/Staff Visitor',
        'maintenance' => 'Building Maintenance',
        'catering' => 'Catering Service',
        'conference' => 'Conference/Event',
        'repair' => 'Repair Service',
        'inspection' => 'Official Inspection',
        'other' => 'Other',
    ];

    #[\Livewire\Attributes\On('refreshComponent')]
    public function refreshComponent()
    {
        $this->resetForm();
        $this->loadGuestTags();
    }

    public function mount()
    {
        $this->loadGuestTags();
    }

    public function loadGuestTags()
    {
        $this->guestTags = GuestPass::where('status', 'available')
            ->latest()
            ->get()
            ->toArray();
    }

    public function resetForm()
    {
        $this->firstname = '';
        $this->middlename = '';
        $this->lastname = '';
        $this->contactNumber = '';
        $this->licensePlate = '';
        $this->vehicleType = ''; // Reset vehicle type
        $this->reason = '';
        $this->selectedTag = '';
        $this->resetErrorBag();
    }

    public function registerGuest()
    {
        $this->validate([
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'middlename' => 'nullable|string|max:255',
            'contactNumber' => 'required|string|max:20',
            'licensePlate' => 'required|string|max:255',
            'vehicleType' => 'required|in:motorcycle,car', // Validation matches view options
            'reason' => 'required|in:' . implode(',', array_keys($this->reasons)),
            'selectedTag' => 'required|exists:guest_passes,id',
        ]);

        try {
            // Find the selected tag
            $tag = GuestPass::find($this->selectedTag);
            
            if (!$tag) {
                $this->addError('selectedTag', 'Guest tag not found.');
                return;
            }

            // Create the user (guest)
            $user = User::create([
                'firstname' => $this->firstname,
                'middlename' => $this->middlename,
                'lastname' => $this->lastname,
                'contact_number' => $this->contactNumber,
                'student_id' => null,
                'employee_id' => null,
                'address' => null,
            ]);

            // Create the vehicle (if license plate provided)
            Vehicle::create([
                'user_id' => $user->id,
                'type' => $this->vehicleType, // Use the selected vehicle type
                'rfid_tag' => $tag->rfid_tag,
                'license_plate' => $this->licensePlate,
            ]);

            // Update the guest tag with all necessary information
            $tag->update([
                'status' => 'in_use',
                'reason' => $this->reason,
                'user_id' => $user->id,
            ]);

            // Close modal and refresh
            $this->dispatch('close-register-guest-modal');
            $this->dispatch('guestRegistered');
            session()->flash('message', 'Guest registered successfully!');
            $this->resetForm();

        } catch (\Exception $e) {
            $this->addError('general', 'Error registering guest: ' . $e->getMessage());
        }
    }

    private function generateGuestEmail()
    {
        // Generate a unique email for the guest
        $baseEmail = strtolower($this->firstname . '.' . $this->lastname) . '+guest@campus.local';
        $email = $baseEmail;
        $counter = 1;

        while (User::where('email', $email)->exists()) {
            $email = strtolower($this->firstname . '.' . $this->lastname) . '+guest' . $counter . '@campus.local';
            $counter++;
        }

        return $email;
    }

    public function render()
    {
        return view('livewire.admin.register-guest-modal-component');
    }
}

