<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Violation;
use App\Models\Vehicle;
use App\Models\User;
use Livewire\WithPagination;
use App\Mail\ViolationThresholdReached;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use App\Models\ActivityLog;
class PendingReportsComponent extends Component
{
    use WithPagination;
    protected string $paginationTheme = 'bootstrap';
    
    // UI / filters
    public $search = '';
    public $reporterType = '';     // '' | 'student' | 'employee' | 'admin'
    public $startDate = null;
    public $endDate = null;
    public $sortOrder = 'desc';    // 'desc' or 'asc'

    public $vehicles = [];
    public $violationInputs = []; // Store all input values
    public $violationStatuses = []; // Store search statuses

    public $perPage = 15; // default
    public $perPageOptions = [15, 25, 50, 100];
    public $pageName = 'page';
    // modal / message fields
public $selectedViolationId = null;

public $approveMessages = [
    'warning' => 'Your report has been reviewed and approved. Thank you for helping us maintain parking discipline.',
    'reminder' => 'Your report was approved. We appreciate your vigilance in reporting parking violations.',
    'penalty' => 'Your report has been approved and necessary actions have been taken against the violator.',
];

public $rejectMessages = [
    'lack_of_evidence' => 'Your report was not approved due to insufficient evidence.',
    'wrong_location' => 'Your report was rejected because the violation location did not match the records.',
    'duplicate' => 'Your report was not processed because it was a duplicate of an existing one.',
];

public $selectedApproveMessage = '';
public $selectedRejectMessage = '';
public $approveCustomMessage = '';
public $rejectCustomMessage = '';


    // reset page when perPage changes
public function updatedPerPage()
{
    // explicitly reset the default "page" paginator
    $this->resetPage('page');
}
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
    \Log::info("=== updateStatus CALLED ===", [
        'violation_id' => $violationId,
        'new_status' => $newStatus,
        'timestamp' => now()
    ]);
    
    $violation = Violation::find($violationId);
    if ($violation) {
        \Log::info("Violation found", [
            'violation_id' => $violationId,
            'current_status' => $violation->status,
            'violator_id' => $violation->violator_id,
            'has_violator_id' => !empty($violation->violator_id)
        ]);
        
        // Use model helper method for approved status
        if ($newStatus === 'approved') {
            $violation->markAsApproved();
        } elseif ($newStatus === 'rejected') {
            $violation->markAsRejected();
        } 
        
        \Log::info("Violation status updated", [
            'violation_id' => $violationId,
            'new_status' => $newStatus,
            'will_check_email' => ($newStatus === 'approved' && $violation->violator_id)
        ]);
        
        // Send email if violation was approved and user now has 3+ violations
        if ($newStatus === 'approved' && $violation->violator_id) {
            \Log::info("Calling checkAndSendThresholdEmail", ['violator_id' => $violation->violator_id]);
            $this->checkAndSendThresholdEmail($violation->violator_id);
        } else {
            \Log::info("Email check skipped", [
                'reason' => $newStatus !== 'approved' ? 'not approved' : 'no violator_id',
                'status' => $newStatus,
                'violator_id' => $violation->violator_id
            ]);
        }
    } else {
        \Log::warning("Violation not found", ['violation_id' => $violationId]);
    }
    
    $this->resetPage();
}
    
private function checkAndSendThresholdEmail($violatorId)
    {
        try {
            $user = User::find($violatorId);
            if (!$user) {
                Log::warning("User not found for violator_id: {$violatorId}");
                return;
            }
            
            $approvedCount = DB::table('violations')
                ->where('violator_id', $user->id)
                ->where('status', 'approved')
                ->count();
            
            // Debug logging
            Log::info("Checking violation threshold for user {$user->id}", [
                'user_email' => $user->email,
                'approved_count' => $approvedCount,
                'will_send_email' => $approvedCount === 3
            ]);
            
            // Send email if they just hit exactly 3 violations
            if ($approvedCount === 3) {
                Mail::to($user->email)
                    ->send(new ViolationThresholdReached($user));
                
                // Log that we sent the email
                Log::info("Sent violation threshold email to user {$user->id} ({$user->email})");
            }
            
        } catch (\Exception $e) {
            Log::error("Failed to send violation threshold email: " . $e->getMessage(), [
                'violator_id' => $violatorId,
                'trace' => $e->getTraceAsString()
            ]);
        }
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
                Log::warning("Violation not found: {$violationId}");
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
                Log::info("Updated violation {$violationId}: plate={$violation->license_plate}, violator_id={$violation->violator_id}");
            }
            
            return true;
            
        } catch (\Exception $e) {
            Log::error("Error updating violation {$violationId}: " . $e->getMessage());
            return false;
        }
    }

 public function render()
    {
        $violationsQuery = Violation::with(['reporter', 'area', 'violator'])
            // ALWAYS pending for this tab
            ->where('status', 'pending');

        // 🔍 SEARCH (license_plate, description, reporter name/id, violator name/id)
        $violationsQuery->when(trim($this->search) !== '', function (Builder $q) {
            $s = trim($this->search);
            $q->where(function (Builder $sub) use ($s) {
                $sub->where('license_plate', 'like', "%{$s}%")
                    ->orWhere('description', 'like', "%{$s}%")
                    ->orWhereHas('reporter', function (Builder $r) use ($s) {
                        $r->where('firstname', 'like', "%{$s}%")
                          ->orWhere('lastname', 'like', "%{$s}%")
                          ->orWhere('student_id', 'like', "%{$s}%")
                          ->orWhere('employee_id', 'like', "%{$s}%")
                          ->orWhereRaw("CONCAT(firstname, ' ', lastname) like ?", ["%{$s}%"]);
                    })
                    ->orWhereHas('violator', function (Builder $v) use ($s) {
                        $v->where('firstname', 'like', "%{$s}%")
                          ->orWhere('lastname', 'like', "%{$s}%")
                          ->orWhere('student_id', 'like', "%{$s}%")
                          ->orWhere('employee_id', 'like', "%{$s}%")
                          ->orWhereRaw("CONCAT(firstname, ' ', lastname) like ?", ["%{$s}%"]);
                    });
            });
        });

        // 🎛 Reporter Type Filter (student / employee)
        $violationsQuery->when($this->reporterType === 'student', fn (Builder $q) =>
            $q->whereHas('reporter', fn (Builder $u) =>
                $u->whereNotNull('student_id')
                  ->where('student_id', '<>', '')
                  ->where('student_id', '<>', '0')
            )
        );

        $violationsQuery->when($this->reporterType === 'employee', fn (Builder $q) =>
            $q->whereHas('reporter', fn (Builder $u) =>
                $u->whereNotNull('employee_id')
                  ->where('employee_id', '<>', '')
                  ->where('employee_id', '<>', '0')
                  ->where(function ($q) {
                      $q->whereNull('student_id')->orWhere('student_id', '');
                  })
            )
        );

        // 📅 Date Range
        $violationsQuery->when($this->startDate, fn (Builder $q) =>
            $q->where('created_at', '>=', Carbon::parse($this->startDate)->startOfDay())
        );
        $violationsQuery->when($this->endDate, fn (Builder $q) =>
            $q->where('created_at', '<=', Carbon::parse($this->endDate)->endOfDay())
        );

        $violations = $violationsQuery
            ->orderBy('created_at', $this->sortOrder === 'asc' ? 'asc' : 'desc')
            ->paginate($this->perPage, ['*'], $this->pageName);

        // Initialize form data for current page violations (keep your existing initialization logic)
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
public function approveWithMessage($violationId)
{
    $this->selectedViolationId = $violationId;
    $this->selectedApproveMessage = '';
    $this->approveCustomMessage = '';
    // ask frontend to open modal
    $this->dispatch('open-approve-modal');
}

public function rejectWithMessage($violationId)
{
    $this->selectedViolationId = $violationId;
    $this->selectedRejectMessage = '';
    $this->rejectCustomMessage = '';
    $this->dispatch('open-reject-modal');
}
public function sendApproveMessage()
{
    $this->validate([
        'selectedApproveMessage' => 'required|string',
        'approveCustomMessage' => 'nullable|string|max:2000',
    ]);

    $messageKey = $this->selectedApproveMessage;
    $message = $messageKey === 'other' ? trim($this->approveCustomMessage) : ($this->approveMessages[$messageKey] ?? null);

    if (!$message) {
        $this->addError('selectedApproveMessage', 'Please select or enter a message.');
        return;
    }

    $violation = Violation::find($this->selectedViolationId);
    if (! $violation) {
        session()->flash('error', 'Violation not found.');
        $this->dispatch('close-approve-modal');
        return;
    }

    // set message and approve
    $violation->action_taken = $message;
    $violation->markAsApproved(); // existing helper
    $violation->save();

    // optional: activity log
    ActivityLog::create([
        'actor_type' => 'admin',
        'actor_id' => optional(auth()->guard('admin')->user())->getKey(),
        'area_id' => $violation->area_id,
        'action' => 'approve_with_message',
        'details' => "Approved #{$violation->id} with message: {$message}",
        'created_at' => now(),
    ]);

    session()->flash('success', 'Violation approved and message sent.');
    $this->dispatch('close-approve-modal');

    // reset modal fields
    $this->selectedViolationId = null;
    $this->selectedApproveMessage = '';
    $this->approveCustomMessage = '';
    $this->resetPage(); // refresh list
}

public function sendRejectMessage()
{
    $this->validate([
        'selectedRejectMessage' => 'required|string',
        'rejectCustomMessage' => 'nullable|string|max:2000',
    ]);

    $messageKey = $this->selectedRejectMessage;
    $message = $messageKey === 'other' ? trim($this->rejectCustomMessage) : ($this->rejectMessages[$messageKey] ?? null);

    if (!$message) {
        $this->addError('selectedRejectMessage', 'Please select or enter a message.');
        return;
    }

    $violation = Violation::find($this->selectedViolationId);
    if (! $violation) {
        session()->flash('error', 'Violation not found.');
        $this->dispatch('close-reject-modal');
        return;
    }

    // set message and reject
    $violation->action_taken = $message;
    $violation->markAsRejected(); // existing helper
    $violation->save();

    ActivityLog::create([
        'actor_type' => 'admin',
        'actor_id' => optional(auth()->guard('admin')->user())->getKey(),
        'area_id' => $violation->area_id,
        'action' => 'reject_with_message',
        'details' => "Rejected #{$violation->id} with message: {$message}",
        'created_at' => now(),
    ]);

    session()->flash('success', 'Violation rejected and message sent.');
    $this->dispatch('close-reject-modal');

    $this->selectedViolationId = null;
    $this->selectedRejectMessage = '';
    $this->rejectCustomMessage = '';
    $this->resetPage();
}

}