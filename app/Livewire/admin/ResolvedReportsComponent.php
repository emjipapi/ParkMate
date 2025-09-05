<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Violation;
use App\Models\Vehicle;
use App\Models\User;

class ResolvedReportsComponent extends Component
{
    public $violations;
    public $violationsActionTaken = [];
    public $activeTab = 'pending';
    public $vehicles = [];
    public $users = [];

    // Search properties for dynamic loading
    public $vehicleSearch = '';
    public $userSearch = '';

    // Limits
    protected $vehicleLimit = 3;
    protected $userLimit = 3;


    public function mount()
    {
        $this->refreshViolations();

        // Load initial vehicles
        $this->vehicles = Vehicle::with('user')
            ->latest()
            ->limit(6)
            ->get()
            ->map(function ($vehicle) {
                return [
                    'id' => $vehicle->id,
                    'license_plate' => $vehicle->license_plate,
                    'user_id' => $vehicle->user_id,
                    'owner_name' => $vehicle->user ? $vehicle->user->firstname . ' ' . $vehicle->user->lastname : null
                ];
            });

        // Load initial users
        $this->users = User::limit($this->userLimit)
            ->get()
            ->map(function ($user) {
                $userVehicles = Vehicle::where('user_id', $user->id)->limit(3)->get();
                return [
                    'id' => $user->id,
                    'firstname' => $user->firstname,
                    'lastname' => $user->lastname,
                    'license_plates' => $userVehicles->pluck('license_plate')->toArray()
                ];
            });
    }
    public $searchTerm = '';
    public $searchResults = [];

    public function updatedSearchTerm()
    {
        if (strlen($this->searchTerm) >= 2) { // start searching after 2 characters
            $this->searchResults = Vehicle::where('user_id', 'like', '%' . $this->searchTerm . '%')
                ->orWhere('license_plate', 'like', '%' . $this->searchTerm . '%')
                ->limit(10)
                ->get();
        } else {
            $this->searchResults = [];
        }
    }







    /**
     * Find violator information by license plate
     * Returns user info if plate exists, null if not found
     */
    public function findViolatorByPlate($licensePlate)
    {
        if (empty($licensePlate)) {
            return null;
        }

        // Only exact match
        $vehicle = Vehicle::where('license_plate', trim($licensePlate))
            ->with('user')
            ->first();

        if ($vehicle && $vehicle->user) {
            return [
                'user_id' => (string) $vehicle->user->id,
                'owner_name' => trim($vehicle->user->firstname . ' ' . $vehicle->user->lastname),
                'license_plate' => $vehicle->license_plate,
                'vehicle_id' => $vehicle->id
            ];
        }

        return null;
    }


    // Helper method to refresh violations with proper relationships
    private function refreshViolations()
    {
        $this->violations = Violation::with(['reporter', 'area', 'violator'])->get()->map(function ($violation) {
            // Populate missing violator_id from license_plate
            if (empty($violation->violator_id) && !empty($violation->license_plate)) {
                $match = $this->findViolatorByPlate($violation->license_plate);
                if ($match) {
                    $violation->violator_id = $match['user_id'];
                    $violation->save(); // only save real fields
                }
            }

            // Populate missing license_plate from violator_id
            if (!empty($violation->violator_id) && empty($violation->license_plate)) {
                $match = $this->findPlatesByViolator($violation->violator_id);
                if ($match && !empty($match['plates'])) {
                    $violation->license_plate = $match['plates'][0];
                    $violation->save(); // only save real fields
                }
            }

            // Add virtual property for the view
            $violation->violator_name = $violation->violator
                ? trim($violation->violator->firstname . ' ' . $violation->violator->lastname)
                : 'Unknown';

            return $violation;
        });
    }

    public function render()
    {
        if (!$this->violations) {
            $this->violations = collect([]);
        }

        return view('livewire.admin.resolved-reports-component', [
            'violations' => $this->violations,
            'vehicles' => $this->vehicles,
            'users' => $this->users
        ]);
    }
}
