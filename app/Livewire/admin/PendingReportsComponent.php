<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Violation;
use App\Models\Vehicle;
use App\Models\User;
use App\Models\Admin;
use App\Models\ViolationMessage;
use Livewire\WithPagination;
use App\Mail\ViolationThresholdReached;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use App\Jobs\SendViolationWarningEmail;

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
    public $pageName = 'pendingPage';
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
    $this->resetPage($this->pageName);
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
        // Clear the suggested violator but keep the original license plate input
        $this->violationStatuses[$violationId]['suggested_violator_id'] = null;
        return;
    }
    
    $this->violationStatuses[$violationId]['plate_status'] = 'loading';
    
    $result = $this->findViolatorByPlate(trim($licensePlate));
    
    if ($result && $result['user_id']) {
        // Store the found data temporarily - DO NOT auto-save to database
        $this->violationStatuses[$violationId]['plate_status'] = 'found';
        $this->violationStatuses[$violationId]['found_owner'] = $result['owner_name'];
        $this->violationStatuses[$violationId]['violator_status'] = 'found';
        $this->violationStatuses[$violationId]['found_violator'] = $result['owner_name'];
        
        // Store suggested violator ID temporarily (for when admin approves)
        $this->violationStatuses[$violationId]['suggested_violator_id'] = $result['user_id'];
        $this->violationStatuses[$violationId]['suggested_license_plate'] = $result['license_plate'];
        
        // Keep the original license plate input unchanged
        // $this->violationInputs[$violationId]['license_plate'] remains as typed by reporter
        
        // REMOVED: Auto-save line - changes are now temporary until approval
        // $this->updateViolation($violationId, trim($licensePlate), $result['user_id']);
    } else {
        $this->violationStatuses[$violationId]['plate_status'] = 'not_found';
        $this->violationStatuses[$violationId]['found_owner'] = '';
        $this->violationStatuses[$violationId]['suggested_violator_id'] = null;
    }
}
    
    // public function searchByViolator($violationId, $violatorId)
    // {
    //     if (empty(trim($violatorId))) {
    //         $this->violationStatuses[$violationId]['violator_status'] = null;
    //         $this->violationStatuses[$violationId]['found_violator'] = '';
    //         return;
    //     }
        
    //     $this->violationStatuses[$violationId]['violator_status'] = 'loading';
        
    //     $result = $this->findPlatesByViolator(trim($violatorId));
        
    //     if ($result && $result['user_data']) {
    //         $this->violationStatuses[$violationId]['violator_status'] = 'found';
    //         $this->violationStatuses[$violationId]['found_violator'] = $result['user_data']['full_name'];
    //         $this->violationStatuses[$violationId]['plate_status'] = 'found';
    //         $this->violationStatuses[$violationId]['found_owner'] = $result['user_data']['full_name'];
            
    //         if (!empty($result['plates'])) {
    //             $this->violationInputs[$violationId]['license_plate'] = $result['plates'][0];
                
    //             // Auto-save
    //             $this->updateViolation($violationId, $result['plates'][0], trim($violatorId));
    //         }
    //     } else {
    //         $this->violationStatuses[$violationId]['violator_status'] = 'not_found';
    //         $this->violationStatuses[$violationId]['found_violator'] = '';
    //     }
    // }
    
public function updateStatus($violationId, $newStatus)
{
    \Log::info("=== updateStatus CALLED ===", [
        'violation_id' => $violationId,
        'new_status' => $newStatus,
        'timestamp' => now()
    ]);

    $violation = Violation::find($violationId);
    if (! $violation) {
        \Log::warning("Violation not found", ['violation_id' => $violationId]);
        return;
    }

    \Log::info("Violation found", [
        'violation_id' => $violationId,
        'current_status' => $violation->status,
        'violator_id' => $violation->violator_id,
        'has_violator_id' => !empty($violation->violator_id)
    ]);

    // If approving and we have suggested data, apply it to the model first
    if ($newStatus === 'approved' && isset($this->violationStatuses[$violationId]['suggested_violator_id'])) {
        $suggestedViolatorId = $this->violationStatuses[$violationId]['suggested_violator_id'];
        $suggestedLicensePlate = $this->violationStatuses[$violationId]['suggested_license_plate'] ?? null;

        \Log::info("Applying suggested data on approval", [
            'violation_id' => $violationId,
            'suggested_violator_id' => $suggestedViolatorId,
            'suggested_license_plate' => $suggestedLicensePlate
        ]);

        if ($suggestedViolatorId) {
            $violation->violator_id = $suggestedViolatorId;
        }
        if ($suggestedLicensePlate) {
            $violation->license_plate = $suggestedLicensePlate;
        }
    }

    // Determine the final violator id we'll be working with (may be null)
    $finalViolatorId = $violation->violator_id;

    // Count how many approved/for_endorsement this user already has BEFORE this approval.
    // This implements your condition: if this count == 0 => send the mail when approving.
    $previousApprovedOrEndorseCount = 0;
    if ($finalViolatorId) {
        $previousApprovedOrEndorseCount = Violation::where('violator_id', $finalViolatorId)
            ->whereIn('status', ['approved', 'for_endorsement'])
            ->count();
    }

    // Use model helper method for approved status
    if ($newStatus === 'approved') {
        $violation->markAsApproved();
    } elseif ($newStatus === 'rejected') {
        $violation->markAsRejected();
    }

    // Save suggested data + status change
    $violation->save(); // Save both status and suggested data changes

    \Log::info("Violation status updated", [
        'violation_id' => $violationId,
        'new_status' => $newStatus,
        'final_violator_id' => $violation->violator_id,
        'final_license_plate' => $violation->license_plate,
        'will_check_email' => ($newStatus === 'approved' && $violation->violator_id)
    ]);
        // CHANGE: Add ActivityLog creation here
    $admin = Auth::guard('admin')->user();
    
    if ($newStatus === 'approved') {
        ActivityLog::create([
            'actor_type' => 'admin',
            'actor_id' => $admin ? $admin->getKey() : null,
            'area_id' => $violation->area_id,
            'action' => 'approve',
            'details' => 'Admin ' 
                . ($admin ? $admin->firstname . ' ' . $admin->lastname : 'Unknown') 
                . ' approved violation #' . $violation->id 
                . ($violation->license_plate ? ' for license plate ' . $violation->license_plate : '') . '.',
            'created_at' => now(),
        ]);
    } elseif ($newStatus === 'rejected') {
        ActivityLog::create([
            'actor_type' => 'admin',
            'actor_id' => $admin ? $admin->getKey() : null,
            'area_id' => $violation->area_id,
            'action' => 'reject',
            'details' => 'Admin ' 
                . ($admin ? $admin->firstname . ' ' . $admin->lastname : 'Unknown') 
                . ' rejected violation #' . $violation->id 
                . ($violation->license_plate ? ' for license plate ' . $violation->license_plate : '') . '.',
            'created_at' => now(),
        ]);
    }

    // Side effects when approved
    if ($newStatus === 'approved' && $violation->violator_id) {
        \Log::info("Handling approval side effects", [
            'violator_id' => $violation->violator_id,
            'previousApprovedOrEndorseCount' => $previousApprovedOrEndorseCount,
        ]);

        $this->handleApprovalSideEffects($violation->violator_id, $previousApprovedOrEndorseCount);
    } else {
        \Log::info("Email check skipped", [
            'reason' => $newStatus !== 'approved' ? 'not approved' : 'no violator_id',
            'status' => $newStatus,
            'violator_id' => $violation->violator_id
        ]);
    }

    $this->resetPage($this->pageName);
}


    
// private function checkAndSendThresholdEmail($violatorId)
//     {
//         try {
//             $user = User::find($violatorId);
//             if (!$user) {
//                 Log::warning("User not found for violator_id: {$violatorId}");
//                 return;
//             }
            
//             $approvedCount = DB::table('violations')
//                 ->where('violator_id', $user->id)
//                 ->where('status', 'approved')
//                 ->count();
            
//             // Debug logging
//             Log::info("Checking violation threshold for user {$user->id}", [
//                 'user_email' => $user->email,
//                 'approved_count' => $approvedCount,
//                 'will_send_email' => $approvedCount === 3
//             ]);
            
//             // Send email if they just hit exactly 3 violations
//             if ($approvedCount === 3) {
//                 Mail::to($user->email)
//                     ->send(new ViolationThresholdReached($user));
                
//                 // Log that we sent the email
//                 Log::info("Sent violation threshold email to user {$user->id} ({$user->email})");
//             }
            
//         } catch (\Exception $e) {
//             Log::error("Failed to send violation threshold email: " . $e->getMessage(), [
//                 'violator_id' => $violatorId,
//                 'trace' => $e->getTraceAsString()
//             ]);
//         }
//     }
    
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

    // public function findPlatesByViolator($input)
    // {
    //     if (empty($input)) {
    //         return null;
    //     }
        
    //     $input = trim($input);
    //     $user = null;
        
    //     if (is_numeric($input)) {
    //         $user = User::find($input);
    //     }
        
    //     if (!$user) {
    //         $user = User::where(function($query) use ($input) {
    //             $query->where('firstname', 'LIKE', '%' . $input . '%')
    //                   ->orWhere('lastname', 'LIKE', '%' . $input . '%')
    //                   ->orWhere('student_id', 'LIKE', '%' . $input . '%')
    //                   ->orWhere('employee_id', 'LIKE', '%' . $input . '%')
    //                   ->orWhereRaw("CONCAT(firstname, ' ', lastname) LIKE ?", ['%' . $input . '%']);
    //         })->first();
    //     }
        
    //     if ($user) {
    //         $vehicles = Vehicle::where('user_id', $user->id)->get();
            
    //         return [
    //             'user_data' => [
    //                 'id' => (string) $user->id,
    //                 'full_name' => trim($user->firstname . ' ' . $user->lastname),
    //                 'firstname' => $user->firstname,
    //                 'lastname' => $user->lastname,
    //                 'student_id' => $user->student_id ?? null,
    //                 'employee_id' => $user->employee_id ?? null,
    //             ],
    //             'plates' => $vehicles->pluck('license_plate')->toArray(),
    //             'vehicles' => $vehicles->map(function($vehicle) {
    //                 return [
    //                     'id' => $vehicle->id,
    //                     'license_plate' => $vehicle->license_plate
    //                 ];
    //             })->toArray()
    //         ];
    //     }
        
    //     return null;
    // }

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
$violationsQuery->when(trim($this->search ?? '') !== '', function ($q) {
    $s = trim($this->search);

    $q->where(function ($sub) use ($s) {
        $sub->where('license_plate', 'like', "%{$s}%")
            ->orWhere('description', 'like', "%{$s}%")

            // reporter models that have student_id / employee_id (example: User)
            ->orWhereHasMorph('reporter', [User::class], function ($r) use ($s) {
                $r->where('firstname', 'like', "%{$s}%")
                  ->orWhere('lastname', 'like', "%{$s}%")
                  ->orWhere('student_id', 'like', "%{$s}%")
                  ->orWhere('employee_id', 'like', "%{$s}%")
                  ->orWhereRaw("CONCAT(firstname, ' ', lastname) like ?", ["%{$s}%"]);
            })

            // reporter models that DON'T have student_id / employee_id (example: Admin)
            ->orWhereHasMorph('reporter', [Admin::class], function ($r) use ($s) {
                $r->where('firstname', 'like', "%{$s}%")
                  ->orWhere('lastname', 'like', "%{$s}%")
                  ->orWhereRaw("CONCAT(firstname, ' ', lastname) like ?", ["%{$s}%"]);
            })

            // violator (if violator is users table)
            ->orWhereHas('violator', function ($v) use ($s) {
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
    $q->whereHasMorph(
        'reporter',
        [User::class],
        fn (Builder $u) => $u
            ->whereNotNull('student_id')
            ->where('student_id', '<>', '')
            ->where('student_id', '<>', '0')
    )
);

/* EMPLOYEE reporters -> reporters that are User and have employee_id (but not a student) */
$violationsQuery->when($this->reporterType === 'employee', fn (Builder $q) =>
    $q->whereHasMorph(
        'reporter',
        [User::class],
        fn (Builder $u) => $u
            ->whereNotNull('employee_id')
            ->where('employee_id', '<>', '')
            ->where('employee_id', '<>', '0')
            ->where(function (Builder $sub) {
                $sub->whereNull('student_id')->orWhere('student_id', '');
            })
    )
);
$violationsQuery->when($this->reporterType === 'admin', fn (Builder $q) =>
    $q->whereHasMorph('reporter', [Admin::class], fn (Builder $a) => $a->whereNotNull('admin_id'))
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

    if (! $message) {
        $this->addError('selectedApproveMessage', 'Please select or enter a message.');
        return;
    }

    $violation = Violation::find($this->selectedViolationId);
    if (! $violation) {
        session()->flash('error', 'Violation not found.');
        $this->dispatch('close-approve-modal');
        return;
    }

    // Apply suggested data if available before approving
    if (isset($this->violationStatuses[$this->selectedViolationId]['suggested_violator_id'])) {
        $suggestedViolatorId = $this->violationStatuses[$this->selectedViolationId]['suggested_violator_id'];
        $suggestedLicensePlate = $this->violationStatuses[$this->selectedViolationId]['suggested_license_plate'] ?? null;
        
        if ($suggestedViolatorId) {
            $violation->violator_id = $suggestedViolatorId;
        }
        if ($suggestedLicensePlate) {
            $violation->license_plate = $suggestedLicensePlate;
        }
    }

    // compute previous count BEFORE changing status
    $finalViolatorId = $violation->violator_id;
    $previousApprovedOrEndorseCount = 0;
    if ($finalViolatorId) {
        $previousApprovedOrEndorseCount = Violation::where('violator_id', $finalViolatorId)
            ->whereIn('status', ['approved', 'for_endorsement'])
            ->count();
    }

    // Approve and save
    $violation->markAsApproved();
    $violation->save();

    // Always create violation_message record (store preset or custom)
    $admin = Auth::guard('admin')->user();
    ViolationMessage::create([
        'violation_id' => $violation->id,
        'sender_id' => $admin ? $admin->getKey() : null,
        'sender_type' => $admin ? get_class($admin) : null,
        'message' => $message,
        'type' => 'approval',
        'created_at' => now(),
    ]);

ActivityLog::create([
    'actor_type' => 'admin',
    'actor_id' => $admin ? $admin->getKey() : null,
    'area_id' => $violation->area_id,
    'action' => 'approve_with_message',
    'details' => 'Admin ' 
        . ($admin ? $admin->firstname . ' ' . $admin->lastname : 'Unknown') 
        . ' approved violation #' . $violation->id 
        . ' with message: "' . $message . '".',
    'created_at' => now(),
]);

    // Handle emails & threshold checks
    if ($finalViolatorId) {
        $this->handleApprovalSideEffects($finalViolatorId, $previousApprovedOrEndorseCount);
    }

    session()->flash('success', 'Violation approved and message saved/sent.');
    $this->dispatch('close-approve-modal');

    // Reset modal state
    $this->selectedViolationId = null;
    $this->selectedApproveMessage = '';
    $this->approveCustomMessage = '';
    $this->resetPage($this->pageName);
}

 public function sendRejectMessage()
    {
        $this->validate([
            'selectedRejectMessage' => 'required|string',
            'rejectCustomMessage' => 'nullable|string|max:2000',
        ]);

        $messageKey = $this->selectedRejectMessage;
        $message = $messageKey === 'other' ? trim($this->rejectCustomMessage) : ($this->rejectMessages[$messageKey] ?? null);

        if (! $message) {
            $this->addError('selectedRejectMessage', 'Please select or enter a message.');
            return;
        }

        $violation = Violation::find($this->selectedViolationId);
        if (! $violation) {
            session()->flash('error', 'Violation not found.');
            $this->dispatch('close-reject-modal');
            return;
        }

        // $violation->action_taken = $message;
        $violation->markAsRejected();
        $violation->save();

        $admin = Auth::guard('admin')->user();
        ViolationMessage::create([
            'violation_id' => $violation->id,
            'sender_id' => $admin ? $admin->getKey() : null,
            'sender_type' => $admin ? get_class($admin) : null,
            'message' => $message,
            'type' => 'rejection',
            'created_at' => now(),
        ]);

ActivityLog::create([
    'actor_type' => 'admin',
    'actor_id' => $admin ? $admin->getKey() : null,
    'area_id' => $violation->area_id,
    'action' => 'reject_with_message',
    'details' => 'Admin ' 
        . ($admin ? $admin->firstname . ' ' . $admin->lastname : 'Unknown') 
        . ' rejected violation #' . $violation->id 
        . ' with message: "' . $message . '".',
    'created_at' => now(),
]);

        session()->flash('success', 'Violation rejected and message saved/sent.');
        $this->dispatch('close-reject-modal');

        $this->selectedViolationId = null;
        $this->selectedRejectMessage = '';
        $this->rejectCustomMessage = '';
        $this->resetPage($this->pageName);
    }
private function handleApprovalSideEffects(int $violatorId, int $previousApprovedOrEndorseCount): void
{
    \Log::info("=== handleApprovalSideEffects START ===", [
        'violator_id' => $violatorId,
        'previous_count' => $previousApprovedOrEndorseCount
    ]);

    $user = User::find($violatorId);
    if (! $user || empty($user->email)) {
        \Log::warning("handleApprovalSideEffects: user not found or no email", [
            'violator_id' => $violatorId,
            'user_found' => $user ? 'yes' : 'no',
            'email_empty' => $user ? empty($user->email) : 'n/a'
        ]);
        return;
    }

    // compute the new count after this approval
    $currentCountAfterSave = $previousApprovedOrEndorseCount + 1;

    // Decide which stage (1,2,3,...) the user just hit (configurable)
    $stages = config('violations.stages', [
        1 => ['threshold' => 1],
        2 => ['threshold' => 2],
        3 => ['threshold' => 3],
    ]);

    $sendStage = null;
    foreach ($stages as $stage => $meta) {
        if (!empty($meta['threshold']) && $meta['threshold'] === $currentCountAfterSave) {
            $sendStage = (int) $stage;
            break;
        }
    }

    if (! $sendStage) {
        \Log::info("No stage matched for this approval", [
            'violator_id' => $violatorId,
            'previous_count' => $previousApprovedOrEndorseCount,
            'current_count_after_save' => $currentCountAfterSave
        ]);
        \Log::info("=== handleApprovalSideEffects END ===");
        return;
    }

    \Log::info("Stage matched - dispatch decision", [
        'violator_id' => $violatorId,
        'sendStage' => $sendStage
    ]);

    try {
        // Always dispatch the job - the job itself is defensive and checks thresholds
        SendViolationWarningEmail::dispatch($user->id, $sendStage);
        \Log::info("Dispatched SendViolationWarningEmail job", ['user_id' => $user->id, 'stage' => $sendStage]);
    } catch (\Throwable $ex) {
        \Log::error("Error dispatching job", [
            'violator_id' => $violatorId,
            'error' => $ex->getMessage(),
            'trace' => $ex->getTraceAsString()
        ]);
    }

    // Optionally log to violation_notifications for record-keeping
    try {
        $schema = DB::getSchemaBuilder();
        if ($schema->hasTable('violation_notifications')) {
            DB::table('violation_notifications')->updateOrInsert(
                ['user_id' => $user->id, 'stage' => $sendStage],
                ['created_at' => now(), 'updated_at' => now()]
            );
            \Log::info("Logged notification to violation_notifications table", ['user_id' => $user->id, 'stage' => $sendStage]);
        }
    } catch (\Exception $e) {
        \Log::warning("Could not log to violation_notifications", ['error' => $e->getMessage()]);
    }

    \Log::info("=== handleApprovalSideEffects END ===");
}




public function approveWithMessageConfirm()
{
    $violationId = $this->selectedViolationId;
    if (! $violationId) {
        return;
    }

    $violation = Violation::find($violationId);
    if (! $violation) {
        return;
    }

    // Optionally store message to violation_messages table
    if (! empty($this->approveCustomMessage)) {
        \DB::table('violation_messages')->insert([
            'violation_id' => $violation->id,
            'sender_id' => auth()->id(),
            'sender_type' => get_class(auth()->user()), // adjust if you use polymorphic
            'message' => $this->approveCustomMessage,
            'type' => 'approval',
            'created_at' => now(),
        ]);
    }

    // Apply suggested data (if any) same as updateStatus flow
    if (isset($this->violationStatuses[$violationId]['suggested_violator_id'])) {
        $suggestedViolatorId = $this->violationStatuses[$violationId]['suggested_violator_id'];
        $suggestedLicensePlate = $this->violationStatuses[$violationId]['suggested_license_plate'] ?? null;
        if ($suggestedViolatorId) $violation->violator_id = $suggestedViolatorId;
        if ($suggestedLicensePlate) $violation->license_plate = $suggestedLicensePlate;
    }

    // compute previous count BEFORE changing status
    $finalViolatorId = $violation->violator_id;
    $previousApprovedOrEndorseCount = 0;
    if ($finalViolatorId) {
        $previousApprovedOrEndorseCount = Violation::where('violator_id', $finalViolatorId)
            ->whereIn('status', ['approved', 'for_endorsement'])
            ->count();
    }

    // Approve and save
    $violation->markAsApproved();
    $violation->save();

    // Handle emails & threshold checks
    if ($finalViolatorId) {
        $this->handleApprovalSideEffects($finalViolatorId, $previousApprovedOrEndorseCount);
    }

    // close modal and notify (same pattern you used)
    $this->dispatch('notify', [
        'type' => 'success',
        'message' => 'Violation approved and message saved.',
    ]);
    $this->dispatch('close-approve-modal'); // wire this up to close the approve modal on frontend if needed
    $this->resetPage($this->pageName);
}
public function updatedSearch()
{
    $this->resetPage($this->pageName);
}

public function updatedReporterType()
{
    $this->resetPage($this->pageName);
}

public function updatedStartDate()
{
    $this->resetPage($this->pageName);
}

public function updatedEndDate()
{
    $this->resetPage($this->pageName);
}

public function updatedSortOrder()
{
    $this->resetPage($this->pageName);
}


}