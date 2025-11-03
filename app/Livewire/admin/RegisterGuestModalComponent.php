<?php

namespace App\Livewire\Admin;

use App\Models\User;
use App\Models\Vehicle;
use App\Models\GuestPass;
use App\Models\GuestRegistration;
use Livewire\Component;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
class RegisterGuestModalComponent extends Component
{
    // User fields
    public $firstname = '';
    public $middlename = '';
    public $lastname = '';
    public $contactNumber = '';
    public $address = '';

    // Vehicle fields
    public $licensePlate = '';
    public $vehicleType = ''; // Added vehicle type

    // Guest fields
    public $reason = '';
    public $customReason = ''; // For "other" reason
    public $selectedTag = '';
    public $office = ''; // New office field

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

    // Search functionality
    public $guestSearch = '';
    public $searchResults = [];
    public $isReturningGuest = false;
    public $originalLicensePlate = ''; // Track original vehicle for returning guests

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
        $this->address = '';
        $this->licensePlate = '';
        $this->vehicleType = '';
        $this->reason = '';
        $this->customReason = '';
        $this->selectedTag = '';
        $this->office = '';
        $this->guestSearch = '';
        $this->searchResults = [];
        $this->isReturningGuest = false;
        $this->originalLicensePlate = '';
        $this->resetErrorBag();
    }

    public function updatedGuestSearch()
    {
        if (strlen($this->guestSearch) < 2) {
            $this->searchResults = [];
            return;
        }
        
        // Get the latest registration per user + vehicle combination
        $results = GuestRegistration::withTrashed()
            ->whereHas('user', function ($q) {
                $q->withTrashed()
                  ->whereNull('student_id')
                  ->whereNull('employee_id')
                  ->where(function ($query) {
                      $query->whereRaw("CONCAT(firstname, ' ', lastname) LIKE ?", ["%{$this->guestSearch}%"])
                            ->orWhere('contact_number', 'LIKE', "%{$this->guestSearch}%");
                  });
            })
            ->orWhere('license_plate', 'LIKE', "%{$this->guestSearch}%")
            ->with(['user' => function ($q) {
                $q->withTrashed();
            }, 'guestPass'])
            ->latest('created_at')
            ->get()
            ->unique(function ($item) {
                // Create a unique key combining user_id and license_plate
                return $item->user_id . '-' . $item->license_plate;
            })
            ->values(); // Re-index the collection
        
        $this->searchResults = $results->toArray();
    }

    public function populateGuestData($registrationId)
    {
        $registration = GuestRegistration::withTrashed()->with(['user' => function ($q) {
            $q->withTrashed();
        }])->find($registrationId);
        
        if (!$registration || !$registration->user) {
            $this->addError('general', 'Registration not found.');
            return;
        }
        
        $user = $registration->user;
        
        $this->firstname = $user->firstname;
        $this->middlename = $user->middlename;
        $this->lastname = $user->lastname;
        $this->contactNumber = $user->contact_number;
        $this->address = $user->address;
        $this->licensePlate = $registration->license_plate;
        $this->vehicleType = $registration->vehicle_type;
        $this->originalLicensePlate = $registration->license_plate; // Store original for comparison
        
        // Check if reason is "other" and populate custom reason
        if ($registration->reason === 'other' || !array_key_exists($registration->reason, $this->reasons)) {
            $this->reason = 'other';
            $this->customReason = $registration->reason;
        } else {
            $this->reason = $registration->reason;
            $this->customReason = '';
        }
        
        $this->office = $registration->office; // Populate office from last visit
        
        $this->selectedTag = '';
        $this->guestSearch = '';
        $this->searchResults = [];
        $this->isReturningGuest = true;
    }

    public function registerGuest()
    {
        $this->validate([
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'middlename' => 'nullable|string|max:255',
            'contactNumber' => 'required|string|max:20',
            'licensePlate' => 'required|string|max:255',
            'vehicleType' => 'required|in:motorcycle,car',
            'reason' => 'required|in:' . implode(',', array_keys($this->reasons)),
            'customReason' => 'nullable|required_if:reason,other|string|max:255',
            'selectedTag' => 'required|exists:guest_passes,id',
            'office' => 'nullable|string|max:255',
        ]);

        try {
            // Find the selected tag
            $tag = GuestPass::find($this->selectedTag);
            
            if (!$tag) {
                $this->addError('selectedTag', 'Guest tag not found.');
                return;
            }

            // Check if user already exists (by name + contact number)
            $existingUser = User::withTrashed()
                ->whereNull('student_id')
                ->whereNull('employee_id')
                ->where('firstname', $this->firstname)
                ->where('lastname', $this->lastname)
                ->where('contact_number', $this->contactNumber)
                ->first();

            if (!$this->isReturningGuest && $existingUser) {
                // NEW registration attempt but guest already exists - DENY
                $this->addError('general', 'This guest already exists. Please search for them instead.');
                return;
            }

            if ($this->isReturningGuest && !$existingUser) {
                // Something went wrong - returning guest flag set but user doesn't exist
                $this->addError('general', 'Error: Guest not found. Please search again.');
                return;
            }

            // Check if this user has an active registration with the same license plate
            $activeRegistrationSamePlate = GuestRegistration::where('user_id', $existingUser->id ?? null)
                ->whereHas('guestPass', function ($q) {
                    $q->where('status', 'in_use');
                })
                ->where('license_plate', $this->licensePlate)
                ->first();

            if ($activeRegistrationSamePlate) {
                // Same user, same vehicle, still active - DENY
                $this->addError('general', 'This vehicle is already checked in. Please clear it first.');
                return;
            }
            if ($this->isReturningGuest) {
                $user = $existingUser;
                
                // Restore the user if they were soft deleted
                if ($user->deleted_at) {
                    $user->restore();
                }
                
                // Check if vehicle changed
                $vehicleChanged = ($this->licensePlate !== $this->originalLicensePlate);
                
                if ($vehicleChanged) {
                    // Vehicle is different - create new vehicle for this visit
                    $vehicle = Vehicle::create([
                        'user_id' => $user->id,
                        'type' => $this->vehicleType,
                        'rfid_tag' => [$tag->rfid_tag],
                        'license_plate' => $this->licensePlate,
                    ]);
                } else {
                    // Same vehicle - use existing or find it
                    $vehicle = Vehicle::withTrashed()
                        ->where('user_id', $user->id)
                        ->where('license_plate', $this->licensePlate)
                        ->first();
                    
                    if (!$vehicle) {
                        // Shouldn't happen, but create if needed
                        $vehicle = Vehicle::create([
                            'user_id' => $user->id,
                            'type' => $this->vehicleType,
                            'rfid_tag' => [$tag->rfid_tag],
                            'license_plate' => $this->licensePlate,
                        ]);
                    }
                }
            } else {
                // NEW GUEST - create user and vehicle
                $user = User::create([
                    'firstname' => $this->firstname,
                    'middlename' => $this->middlename,
                    'lastname' => $this->lastname,
                    'contact_number' => $this->contactNumber,
                    'address' => $this->address,
                    'student_id' => null,
                    'employee_id' => null,
                ]);

                // Create the vehicle with RFID tag as array
                Vehicle::create([
                    'user_id' => $user->id,
                    'type' => $this->vehicleType,
                    'rfid_tag' => [$tag->rfid_tag],
                    'license_plate' => $this->licensePlate,
                ]);
            }

            // Create guest registration
            $finalReason = $this->reason === 'other' ? $this->customReason : $this->reason;
            
            GuestRegistration::create([
                'user_id' => $user->id,
                'guest_pass_id' => $tag->id,
                'reason' => $finalReason,
                'vehicle_type' => $this->vehicleType,
                'license_plate' => $this->licensePlate,
                'registered_by' => Auth::guard('admin')->id(),
                'office' => $this->office,
            ]);

            // Update the guest tag
            $tag->update([
                'status' => 'in_use',
                'user_id' => $user->id,
            ]);

            // Log activity
            $reasonLabel = $this->reason === 'other' ? $this->customReason : ($this->reasons[$this->reason] ?? $this->reason);
            
            ActivityLog::create([
                'actor_type' => 'admin',
                'actor_id'   => Auth::guard('admin')->id(),
                'action'     => 'create',
                'details'    => 'Admin ' 
                    . Auth::guard('admin')->user()->firstname . ' ' 
                    . Auth::guard('admin')->user()->lastname 
                    . ' registered guest "' 
                    . $this->firstname . ' ' . $this->lastname 
                    . '" with vehicle ' . $this->vehicleType . ' (' . $this->licensePlate . ')'
                    . ' for reason: ' . $reasonLabel
                    . ' going to office: ' . ($this->office ?: 'Not specified')
                    . ' using tag "' . $tag->name . '" (RFID: ' . $tag->rfid_tag . ').',
            ]);

            // Close modal and refresh
            $this->dispatch('guestRegistered');
            session()->flash('message', 'Guest registered successfully!');
            $this->resetForm();

            // Close and reopen the modal
            $this->js('
                const modalEl = document.getElementById("registerGuestModal");
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
                setTimeout(() => {
                    const newModal = new bootstrap.Modal(modalEl);
                    newModal.show();
                }, 500);
            ');

        } catch (\Exception $e) {
            $this->addError('general', 'Error registering guest: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.admin.register-guest-modal-component');
    }
}

