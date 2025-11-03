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
     * Get entry and exit location for a guest registration
     */
    private function getLocationSummary($registrationId, $userId)
    {
        // Get the most recent area scan (entry to any parking area)
        $areaEntry = ActivityLog::where('actor_type', 'user')
            ->where('actor_id', $userId)
            ->where('action', 'entry')
            ->whereNotNull('area_id')
            ->where('created_at', '>=', now()->subHours(24))
            ->latest('created_at')
            ->first();

        // Check if there's a main gate exit after the area entry
        $mainGateExit = null;
        if ($areaEntry) {
            $mainGateExit = ActivityLog::where('actor_type', 'user')
                ->where('actor_id', $userId)
                ->where('action', 'exit')
                ->whereNull('area_id')
                ->where('created_at', '>', $areaEntry->created_at)
                ->where('created_at', '>=', now()->subHours(24))
                ->latest('created_at')
                ->first();
        }

        // If they exited to main gate, show that; otherwise show area exit
        $exitLog = $mainGateExit ?: ActivityLog::where('actor_type', 'user')
            ->where('actor_id', $userId)
            ->where('action', 'exit')
            ->whereNotNull('area_id')
            ->where('created_at', '>=', now()->subHours(24))
            ->latest('created_at')
            ->first();

        // Extract locations
        $entryLocation = $areaEntry ? $this->extractAreaName($areaEntry->details) : 'Entry not recorded';
        $exitLocation = $exitLog 
            ? ($exitLog->area_id ? $this->extractAreaName($exitLog->details) : 'Main Gate')
            : 'Still inside';

        return $entryLocation . ' → ' . $exitLocation;
    }

    /**
     * Extract area name from activity log details
     */
    private function extractAreaName($details)
    {
        // Try to extract area name - look for patterns like "area {name}" before the period
        if (preg_match('/area\s+([^.]+)/i', $details, $matches)) {
            $areaName = trim($matches[1]);
            // Remove everything after the first pipe if exists
            if (strpos($areaName, '|') !== false) {
                $areaName = trim(explode('|', $areaName)[0]);
            }
            return $areaName ?: 'Parking Area';
        }
        
        return 'Parking Area';
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

            // Clear RFID tags from the vehicle
            if ($user) {
                $vehicle = $user->vehicles()
                    ->where('license_plate', $registration->license_plate)
                    ->first();
                
                if ($vehicle) {
                    $vehicle->update([
                        'rfid_tag' => null,
                    ]);
                }
            }

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