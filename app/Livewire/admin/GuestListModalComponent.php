<?php

namespace App\Livewire\Admin;

use App\Models\GuestPass;
use App\Models\GuestRegistration;
use Livewire\Component;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
        // Get the most recent entry (by ID) within last 24 hours
        $mostRecentEntry = ActivityLog::where('actor_type', 'user')
            ->where('actor_id', $userId)
            ->where('action', 'entry')
            ->where('created_at', '>=', now()->subHours(24))
            ->orderByDesc('id')
            ->first();

        if (!$mostRecentEntry) {
            return 'Entry not recorded';
        }

        // Determine entry location
        $entryLocation = $mostRecentEntry->area_id 
            ? $this->extractAreaName($mostRecentEntry->details)
            : 'Main Gate';

        // Check if there's an exit AFTER this entry (by ID)
        $exitAfterEntry = ActivityLog::where('actor_type', 'user')
            ->where('actor_id', $userId)
            ->where('action', 'exit')
            ->where('id', '>', $mostRecentEntry->id)
            ->where('created_at', '>=', now()->subHours(24))
            ->orderByDesc('id')
            ->first();

        // Extract exit location or mark as still inside
        $exitLocation = $exitAfterEntry
            ? ($exitAfterEntry->area_id ? $this->extractAreaName($exitAfterEntry->details) : 'Main Gate')
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
            $now = now();

            // Soft delete the registration only
            $registration->delete();

            // Auto-exit from all active areas
            if ($user) {
                // Find all areas the user is currently in
                $activeAreaIds = [];
                $allUserAreas = ActivityLog::where('actor_type', 'user')
                    ->where('actor_id', $user->id)
                    ->whereNotNull('area_id')
                    ->distinct()
                    ->pluck('area_id');

                foreach ($allUserAreas as $areaId) {
                    $lastAreaScan = ActivityLog::where('actor_type', 'user')
                        ->where('actor_id', $user->id)
                        ->where('area_id', $areaId)
                        ->orderBy('created_at', 'desc')
                        ->first();

                    // If last scan was entry, they're still in this area
                    if ($lastAreaScan && $lastAreaScan->action === 'entry') {
                        $activeAreaIds[] = $areaId;
                    }
                }

                // Get vehicle info for motorcycle count updates
                $vehicle = $user->vehicles()
                    ->where('license_plate', $registration->license_plate)
                    ->first();

                // Exit from all active areas
                foreach ($activeAreaIds as $areaId) {
                    $area = \App\Models\ParkingArea::find($areaId);
                    $areaName = $area ? $area->name : 'Unknown area';

                    // Handle motorcycle count
                    if ($vehicle && $vehicle->type === 'motorcycle') {
                        $moto = DB::table('motorcycle_counts')
                            ->where('area_id', $areaId)
                            ->first();

                        if ($moto && $moto->available_count < $moto->total_available) {
                            DB::table('motorcycle_counts')
                                ->where('id', $moto->id)
                                ->increment('available_count');
                        }
                    }

                    // Create exit log
                    ActivityLog::create([
                        'actor_type' => 'admin',
                        'actor_id'   => Auth::guard('admin')->id(),
                        'action'     => 'exit',
                        'details'    => "User {$user->firstname} {$user->lastname} automatically exited area {$areaName} (guest cleared by admin). | Vehicle: " . ($vehicle ? $vehicle->type : 'unknown'),
                        'area_id'    => $areaId,
                        'created_at' => $now,
                    ]);
                }

                // Also exit from main gate if they entered
                $lastMainGateScan = ActivityLog::where('actor_type', 'user')
                    ->where('actor_id', $user->id)
                    ->whereNull('area_id')
                    ->whereIn('action', ['entry', 'exit'])
                    ->orderBy('created_at', 'desc')
                    ->first();

                // If last main gate scan was entry, create an exit log
                if ($lastMainGateScan && $lastMainGateScan->action === 'entry') {
                    ActivityLog::create([
                        'actor_type' => 'admin',
                        'actor_id'   => Auth::guard('admin')->id(),
                        'action'     => 'exit',
                        'details'    => "User {$user->firstname} {$user->lastname} automatically exited main gate (guest cleared by admin). | Vehicle: " . ($vehicle ? $vehicle->type : 'unknown'),
                        'area_id'    => null,
                        'created_at' => $now,
                    ]);
                }

                // Clear RFID tags from the vehicle
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