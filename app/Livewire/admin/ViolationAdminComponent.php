<?php
namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Violation;
use App\Models\Vehicle;
use App\Models\User;

class ViolationAdminComponent extends Component
{
    public $violations;
    public $activeTab = 'pending';
    public $vehicles = []; // Limit initial load
    public $users = [];    // Limit initial load
    
    // Search properties for dynamic loading
    public $vehicleSearch = '';
    public $userSearch = '';
    
    // Limits
    protected $vehicleLimit = 3;
    protected $userLimit = 3;

    public function mount()
    {
        // Initialize violations with violator names
        $this->violations = Violation::with(['reporter', 'area', 'violator'])->get()->map(function($violation) {
            // Add violator_name property for easier access in the view
            $violation->violator_name = $violation->violator ? 
                $violation->violator->firstname . ' ' . $violation->violator->lastname : 
                'Unknown';
            return $violation;
        });
        
        // Load only recent/most used vehicles initially (limit 50)
        $this->vehicles = Vehicle::with('user')
            ->latest() // or ->orderBy('license_plate')
            ->limit(6)
            ->get()
            ->map(function($vehicle) {
                return [
                    'id' => $vehicle->id,
                    'license_plate' => $vehicle->license_plate,
                    'user_id' => $vehicle->user_id,
                    'owner_name' => $vehicle->user ? $vehicle->user->firstname . ' ' . $vehicle->user->lastname : null
                ];
            });
        
        // Load only recent/active users initially (limit 50)
        $this->users = User::limit($this->userLimit)
            ->get()
            ->map(function($user) {
                $userVehicles = Vehicle::where('user_id', $user->id)->limit(3)->get(); 
                return [
                    'id' => $user->id,
                    'firstname' => $user->firstname,
                    'lastname' => $user->lastname,
                    'license_plates' => $userVehicles->pluck('license_plate')->toArray()
                ];
            });
    }

    // Dynamic vehicle search - called when user types in vehicle dropdown
    public function searchVehicles($search)
    {
        if (strlen($search) < 2) {
            return $this->vehicles; // Return default list for short searches
        }

        $searchResults = Vehicle::with('user')
            ->where(function($query) use ($search) {
                $query->where('license_plate', 'LIKE', "%{$search}%")
                      ->orWhereHas('user', function($q) use ($search) {
                          $q->where('firstname', 'LIKE', "%{$search}%")
                            ->orWhere('lastname', 'LIKE', "%{$search}%");
                      });
            })
            ->limit($this->vehicleLimit)
            ->get()
            ->map(function($vehicle) {
                return [
                    'id' => $vehicle->id,
                    'license_plate' => $vehicle->license_plate,
                    'user_id' => $vehicle->user_id,
                    'owner_name' => $vehicle->user ? $vehicle->user->firstname . ' ' . $vehicle->user->lastname : null
                ];
            });

        return $searchResults;
    }

    // Dynamic user search - called when user types in user dropdown
    public function searchUsers($search)
    {
        if (strlen($search) < 2) {
            return $this->users; // Return default list for short searches
        }

        $searchResults = User::where(function($query) use ($search) {
                $query->where('id', 'LIKE', "%{$search}%")
                      ->orWhere('firstname', 'LIKE', "%{$search}%")
                      ->orWhere('lastname', 'LIKE', "%{$search}%")
                      ->orWhere('student_id', 'LIKE', "%{$search}%")
                      ->orWhere('employee_id', 'LIKE', "%{$search}%");
            })
            ->limit($this->userLimit)
            ->get()
            ->map(function($user) {
                $userVehicles = Vehicle::where('user_id', $user->id)->limit(3)->get();
                return [
                    'id' => $user->id,
                    'firstname' => $user->firstname,
                    'lastname' => $user->lastname,
                    'license_plates' => $userVehicles->pluck('license_plate')->toArray()
                ];
            });

        return $searchResults;
    }

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function updateStatus($violationId, $newStatus)
    {
        $violation = Violation::find($violationId);

        if ($violation) {
            $violation->status = $newStatus;
            $violation->save();
        }

        // Reload violations after update
        $this->refreshViolations();
    }

    public function updateVehicle($violationId, $licensePlate, $userId = null)
    {
        $violation = Violation::find($violationId);
        if ($violation) {
            $violation->license_plate = $licensePlate;
            
            // If userId is provided, also update the violator
            if ($userId) {
                $violation->violator_id = $userId;
            } else {
                // Try to find the user by license plate if no userId provided
                $vehicle = Vehicle::where('license_plate', $licensePlate)->with('user')->first();
                if ($vehicle && $vehicle->user) {
                    $violation->violator_id = $vehicle->user->id;
                }
            }
            
            $violation->save();
        }
        
        $this->refreshViolations();
    }

    public function updateViolator($violationId, $userId)
    {
        $violation = Violation::find($violationId);
        if ($violation) {
            $violation->violator_id = $userId;
            
            // Find user's primary vehicle and update license plate
            $userVehicle = Vehicle::where('user_id', $userId)->first();
            if ($userVehicle) {
                $violation->license_plate = $userVehicle->license_plate;
            }
            
            $violation->save();
        }
        
        $this->refreshViolations();
    }

    // Helper method to refresh violations with proper relationships
    private function refreshViolations()
    {
        $this->violations = Violation::with(['reporter', 'area', 'violator'])->get()->map(function($violation) {
            // Add violator_name property for easier access in the view
            $violation->violator_name = $violation->violator ? 
                $violation->violator->firstname . ' ' . $violation->violator->lastname : 
                'Unknown';
            return $violation;
        });
    }

    // Method to find vehicles by license plate (for frontend validation)
    public function findVehicleByPlate($licensePlate)
    {
        $vehicle = Vehicle::where('license_plate', $licensePlate)->with('user')->first();
        
        if ($vehicle) {
            return [
                'id' => $vehicle->id,
                'license_plate' => $vehicle->license_plate,
                'user_id' => $vehicle->user_id,
                'owner_name' => $vehicle->user ? $vehicle->user->firstname . ' ' . $vehicle->user->lastname : null
            ];
        }
        
        return null;
    }

    // Method to find user by ID (for frontend validation)
    public function findUserById($userId)
    {
        $user = User::find($userId);
        
        if ($user) {
            $userVehicles = Vehicle::where('user_id', $user->id)->limit(3)->get();
            return [
                'id' => $user->id,
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'license_plates' => $userVehicles->pluck('license_plate')->toArray()
            ];
        }
        
        return null;
    }

    public function render()
    {
        if (!$this->violations) {
            $this->violations = collect([]);
        }

        return view('livewire.admin.violation-admin-component', [
            'violations' => $this->violations,
            'vehicles' => $this->vehicles,
            'users' => $this->users
        ]);
    }
}