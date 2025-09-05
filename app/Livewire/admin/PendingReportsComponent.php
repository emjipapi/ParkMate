<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Violation;
use App\Models\Vehicle;
use App\Models\User;
use Livewire\WithPagination;

class PendingReportsComponent extends Component
{
    use WithPagination;
    protected string $paginationTheme = 'bootstrap';
    
    public $vehicles = [];
    public $violationInputs = []; // Store all input values
    public $violationStatuses = []; // Store search statuses
    
    public function mount()
    {
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
    }
    
    public function updatedViolationInputs($value, $key)
    {
        $parts = explode('.', $key);
        if (count($parts) !== 2) return;
        
        [$violationId, $field] = $parts;
        
        if ($field === 'license_plate') {
            $this->searchByPlate($violationId, $value);
        } elseif ($field === 'violator_id') {
            $this->searchByViolator($violationId, $value);
        }
    }
    
    public function searchByPlate($violationId, $licensePlate)
    {
        if (empty(trim($licensePlate))) {
            $this->violationStatuses[$violationId]['plate_status'] = null;
            $this->violationStatuses[$violationId]['found_owner'] = '';
            return;
        }
        
        $this->violationStatuses[$violationId]['plate_status'] = 'loading';
        
        $result = $this->findViolatorByPlate(trim($licensePlate));
        
        if ($result && $result['user_id']) {
            $this->violationInputs[$violationId]['violator_id'] = $result['user_id'];
            $this->violationStatuses[$violationId]['plate_status'] = 'found';
            $this->violationStatuses[$violationId]['found_owner'] = $result['owner_name'];
            $this->violationStatuses[$violationId]['violator_status'] = 'found';
            $this->violationStatuses[$violationId]['found_violator'] = $result['owner_name'];
            
            // Auto-save
            $this->updateViolation($violationId, trim($licensePlate), $result['user_id']);
        } else {
            $this->violationStatuses[$violationId]['plate_status'] = 'not_found';
            $this->violationStatuses[$violationId]['found_owner'] = '';
        }
    }
    
    public function searchByViolator($violationId, $violatorId)
    {
        if (empty(trim($violatorId))) {
            $this->violationStatuses[$violationId]['violator_status'] = null;
            $this->violationStatuses[$violationId]['found_violator'] = '';
            return;
        }
        
        $this->violationStatuses[$violationId]['violator_status'] = 'loading';
        
        $result = $this->findPlatesByViolator(trim($violatorId));
        
        if ($result && $result['user_data']) {
            $this->violationStatuses[$violationId]['violator_status'] = 'found';
            $this->violationStatuses[$violationId]['found_violator'] = $result['user_data']['full_name'];
            $this->violationStatuses[$violationId]['plate_status'] = 'found';
            $this->violationStatuses[$violationId]['found_owner'] = $result['user_data']['full_name'];
            
            if (!empty($result['plates'])) {
                $this->violationInputs[$violationId]['license_plate'] = $result['plates'][0];
                
                // Auto-save
                $this->updateViolation($violationId, $result['plates'][0], trim($violatorId));
            }
        } else {
            $this->violationStatuses[$violationId]['violator_status'] = 'not_found';
            $this->violationStatuses[$violationId]['found_violator'] = '';
        }
    }
    
    public function updateStatus($violationId, $newStatus)
    {
        $violation = Violation::find($violationId);
        if ($violation) {
            $violation->status = $newStatus;
            $violation->save();
        }
        $this->resetPage();
    }
    
    public function findViolatorByPlate($licensePlate)
    {
        if (empty($licensePlate)) {
            return null;
        }

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

    public function findPlatesByViolator($input)
    {
        if (empty($input)) {
            return null;
        }
        
        $input = trim($input);
        $user = null;
        
        if (is_numeric($input)) {
            $user = User::find($input);
        }
        
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
            $vehicles = Vehicle::where('user_id', $user->id)->get();
            
            return [
                'user_data' => [
                    'id' => (string) $user->id,
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

    public function updateViolation($violationId, $licensePlate = null, $violatorId = null)
    {
        try {
            $violation = Violation::find($violationId);
            
            if (!$violation) {
                \Log::warning("Violation not found: {$violationId}");
                return false;
            }
            
            $updated = false;
            
            if ($licensePlate !== null && $violation->license_plate !== trim($licensePlate)) {
                $violation->license_plate = trim($licensePlate);
                $updated = true;
            }
            
            if ($violatorId !== null && is_numeric($violatorId)) {
                $violatorId = (int) $violatorId;
                if ($violation->violator_id !== $violatorId) {
                    $violation->violator_id = $violatorId;
                    $updated = true;
                }
            }
            
            if ($updated) {
                $violation->save();
                \Log::info("Updated violation {$violationId}: plate={$violation->license_plate}, violator_id={$violation->violator_id}");
            }
            
            return true;
            
        } catch (\Exception $e) {
            \Log::error("Error updating violation {$violationId}: " . $e->getMessage());
            return false;
        }
    }

    public function render()
    {
        $violations = Violation::with(['reporter', 'area', 'violator'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->paginate(2);

        // Initialize form data for current page violations
        foreach ($violations as $violation) {
            if (!isset($this->violationInputs[$violation->id])) {
                $this->violationInputs[$violation->id] = [
                    'license_plate' => $violation->license_plate ?? '',
                    'violator_id' => $violation->violator_id ?? '',
                ];
                
                $this->violationStatuses[$violation->id] = [
                    'plate_status' => null,
                    'violator_status' => null,
                    'found_owner' => '',
                    'found_violator' => '',
                ];
                
                // Set initial status if data exists
                if ($violation->violator) {
                    $violatorName = trim($violation->violator->firstname . ' ' . $violation->violator->lastname);
                    $this->violationStatuses[$violation->id]['plate_status'] = 'found';
                    $this->violationStatuses[$violation->id]['violator_status'] = 'found';
                    $this->violationStatuses[$violation->id]['found_owner'] = $violatorName;
                    $this->violationStatuses[$violation->id]['found_violator'] = $violatorName;
                }
            }
        }

        return view('livewire.admin.pending-reports-component', [
            'violations' => $violations,
            'vehicles' => $this->vehicles,
        ]);
    }
}