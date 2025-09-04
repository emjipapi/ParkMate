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
            ->map(function($vehicle) {
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

        $this->refreshViolations();
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
        
        // Search for exact match first, then partial match
        $vehicle = Vehicle::where('license_plate', trim($licensePlate))
                         ->with('user')
                         ->first();
        
        // If no exact match, try partial match
        if (!$vehicle) {
            $vehicle = Vehicle::where('license_plate', 'LIKE', '%' . trim($licensePlate) . '%')
                             ->with('user')
                             ->first();
        }
        
        if ($vehicle && $vehicle->user) {
            return [
                'user_id' => (string) $vehicle->user->id, // Convert to string for consistency
                'owner_name' => trim($vehicle->user->firstname . ' ' . $vehicle->user->lastname),
                'license_plate' => $vehicle->license_plate,
                'vehicle_id' => $vehicle->id
            ];
        }
        
        return null;
    }

    /**
     * Find license plates by violator ID or name
     * Returns user data and their plates if found
     */
    public function findPlatesByViolator($input)
    {
        if (empty($input)) {
            return null;
        }
        
        $input = trim($input);
        $user = null;
        
        // Try to find by ID first (if input is numeric)
        if (is_numeric($input)) {
            $user = User::find($input);
        }
        
        // If not found by ID, try searching by name, student_id, or employee_id
        if (!$user) {
            $user = User::where(function($query) use ($input) {
                $query->where('firstname', 'LIKE', '%' . $input . '%')
                      ->orWhere('lastname', 'LIKE', '%' . $input . '%')
                      ->orWhere('student_id', 'LIKE', '%' . $input . '%')
                      ->orWhere('employee_id', 'LIKE', '%' . $input . '%')
                      ->orWhereRaw("CONCAT(firstname, ' ', lastname) LIKE ?", ['%' . $input . '%']);
            })->first();
        }
        
        if ($user) {
            // Get user's vehicles
            $vehicles = Vehicle::where('user_id', $user->id)->get();
            
            return [
                'user_data' => [
                    'id' => (string) $user->id, // Convert to string for consistency
                    'full_name' => trim($user->firstname . ' ' . $user->lastname),
                    'firstname' => $user->firstname,
                    'lastname' => $user->lastname,
                    'student_id' => $user->student_id ?? null,
                    'employee_id' => $user->employee_id ?? null,
                ],
                'plates' => $vehicles->pluck('license_plate')->toArray(),
                'vehicles' => $vehicles->map(function($vehicle) {
                    return [
                        'id' => $vehicle->id,
                        'license_plate' => $vehicle->license_plate
                    ];
                })->toArray()
            ];
        }
        
        return null;
    }

    /**
     * Update violation with new license plate and violator data
     * This combines both updates in one method
     */
    public function updateViolation($violationId, $licensePlate = null, $violatorId = null)
    {
        try {
            $violation = Violation::find($violationId);
            
            if (!$violation) {
                \Log::warning("Violation not found: {$violationId}");
                return false;
            }
            
            $updated = false;
            
            // Update license plate if provided and different
            if ($licensePlate !== null && $violation->license_plate !== trim($licensePlate)) {
                $violation->license_plate = trim($licensePlate);
                $updated = true;
            }
            
            // Update violator if provided and different
            if ($violatorId !== null && is_numeric($violatorId)) {
                $violatorId = (int) $violatorId;
                if ($violation->violator_id !== $violatorId) {
                    $violation->violator_id = $violatorId;
                    $updated = true;
                }
            }
            
            // Only save if something actually changed
            if ($updated) {
                $violation->save();
                
                // Log the update for debugging
                \Log::info("Updated violation {$violationId}: plate={$violation->license_plate}, violator_id={$violation->violator_id}");
                
                // Refresh the violations collection
                $this->refreshViolations();
            }
            
            return true;
            
        } catch (\Exception $e) {
            \Log::error("Error updating violation {$violationId}: " . $e->getMessage());
            return false;
        }
    }

    // Helper method to refresh violations with proper relationships
    private function refreshViolations()
    {
        $this->violations = Violation::with(['reporter', 'area', 'violator'])->get()->map(function($violation) {
            // Add violator_name property for easier access in the view
            $violation->violator_name = $violation->violator ? 
                trim($violation->violator->firstname . ' ' . $violation->violator->lastname) : 
                'Unknown';
            return $violation;
        });
    }

    // Legacy methods (keep for backward compatibility)
    public function searchVehicles($search)
    {
        if (strlen($search) < 2) {
            return $this->vehicles;
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

    public function searchUsers($search)
    {
        if (strlen($search) < 2) {
            return $this->users;
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