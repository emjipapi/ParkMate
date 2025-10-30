<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Violation;
use App\Models\ParkingArea;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\ActivityLog;
use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class CreateViolationComponent extends Component
{
    use WithFileUploads;

     public $description;
    public $otherDescription;
    public $license_plate; // keep snake_case
    public $violator;      // user id or null

    public $areas = [];
    public $area_id;
    public $customArea = '';
    public $evidence;
    public bool $isUploadingEvidence = false;

    public $violatorStatus = null;
    public $violatorName = null;
    

    public function mount()
    {
        // ensure admin is authenticated
        if (! Auth::guard('admin')->check()) {
            abort(403);
        }

        $this->areas = ParkingArea::all();
    }

    #[\Livewire\Attributes\On('upload:starting')]
    public function handleUploadStart()
    {
        $this->isUploadingEvidence = true;
    }

    #[\Livewire\Attributes\On('upload:finished')]
    public function handleUploadFinished()
    {
        $this->isUploadingEvidence = false;
    }

public $violator_id = null;

  public function updatedLicensePlate($value)
    {
        $this->violatorStatus = 'loading';
        $this->violatorName = null;
        $this->violator = null;

        $plate = strtoupper(trim($value));
        if ($plate === '') {
            $this->violatorStatus = null;
            return;
        }

        $vehicle = Vehicle::whereRaw('UPPER(license_plate) = ?', [$plate])
            ->with('user')
            ->first();

        if ($vehicle && $vehicle->user) {
            $this->violatorStatus = 'found';
            $this->violatorName = trim($vehicle->user->firstname . ' ' . $vehicle->user->lastname);
            $this->violator = $vehicle->user->id;
        } else {
            $this->violatorStatus = 'not_found';
            $this->violatorName = null;
            $this->violator = null;
        }
    }

    public function submitReport($status = 'approved')
    {
        // Check if upload is still in progress
        if ($this->isUploadingEvidence) {
            session()->flash('error', 'Please wait for the image upload to complete.');
            return;
        }

        // Defensive: re-run lookup (synchronous, server-side) to avoid race conditions
        $plate = strtoupper(trim($this->license_plate ?? ''));
        $vehicle = null;
        if ($plate !== '') {
            $vehicle = Vehicle::whereRaw('UPPER(license_plate) = ?', [$plate])
                              ->with('user')
                              ->first();
        }

        if ($vehicle && $vehicle->user) {
            $this->violator = $vehicle->user->id;
            $this->violatorStatus = 'found';
        } else {
            $this->violator = null;
            $this->violatorStatus = 'not_found';
        }

        // If we require a found violator for 'approved' submissions, block it
        if ($status !== 'pending' && $this->violator === null) {
            session()->flash('error', 'No violator found for that license plate. Please check the plate or submit as pending.');
            return;
        }

        // Validate (violator may be null for pending)
        $this->validate([
            'description' => 'required|string',
            'area_id' => 'required|string',
            'customArea' => 'nullable|required_if:area_id,other|string|max:255',
            'evidence' => 'required|file|mimes:jpg,jpeg,png|max:10240',
            'license_plate' => 'required|string|max:255',
            'violator' => 'nullable|integer|exists:users,id',
        ]);

        // Additional validation: if area_id is not 'other', it must be a valid parking area
        if ($this->area_id !== 'other') {
            if (!is_numeric($this->area_id) || !ParkingArea::find($this->area_id)) {
                $this->addError('area_id', 'Invalid area selected.');
                return;
            }
        }

        $desc = $this->description === "Other" ? $this->otherDescription : $this->description;
        
        $evidenceData = [
            'reported' => $status === 'pending' ? ($this->evidence ? $this->evidence->store('evidence/reported', 'public') : null) : null,
            'approved' => $status === 'approved' ? ($this->evidence ? $this->evidence->store('evidence/reported', 'public') : null) : null,
        ];

    $admin = Auth::guard('admin')->user();
    if (! $admin) abort(403, 'Admin not authenticated');

    // **NEW: Count violations BEFORE creating the new one**
    $previousApprovedOrEndorseCount = 0;
    if ($status === 'approved' && $this->violator) {
        $previousApprovedOrEndorseCount = Violation::where('violator_id', $this->violator)
            ->whereIn('status', ['approved', 'for_endorsement'])
            ->count();
    }

    $data = [
        'description'   => $desc,
        'evidence'      => $evidenceData,
        'area_id'       => $this->area_id !== 'other' ? (int)$this->area_id : null,
        'custom_area'   => $this->area_id === 'other' ? $this->customArea : null,
        'license_plate' => $plate ?: null,
        'violator_id'   => $this->violator,
        'status'        => $status,
        'submitted_at'  => now(),
    ];

    $violation = $admin->reportedViolations()->create($data);

$area = $this->area_id !== 'other' ? ParkingArea::find($this->area_id) : null;

$licensePlate = !empty($this->license_plate)
    ? strtoupper($this->license_plate)
    : '(empty)';

$areaName = $area
    ? $area->name
    : ($this->customArea ?: '(empty)');

ActivityLog::create([
    'actor_type' => 'admin',
    'actor_id'   => $admin->getKey(),
    'area_id'    => $this->area_id !== 'other' ? $this->area_id : null,
    'action'     => 'report',
    'details'    => "Admin {$admin->firstname} created a {$status} violation report "
                  . "with license plate {$licensePlate} in area {$areaName}.",
    'created_at' => now(),
]);



    // **NEW: Handle approval side effects (send email job)**
    if ($status === 'approved' && $this->violator) {
        $this->handleApprovalSideEffects($this->violator, $previousApprovedOrEndorseCount);
    }

    session()->flash('success', "Report submitted as {$status} successfully!");
    $this->resetFormInputs();
}
private function handleApprovalSideEffects(int $violatorId, int $previousApprovedOrEndorseCount): void
{
    \Log::info("=== handleApprovalSideEffects START (CreateViolation) ===", [
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
        $schema = \DB::getSchemaBuilder();
        if ($schema->hasTable('violation_notifications')) {
            $inserted = \DB::table('violation_notifications')->insertOrIgnore([
                'user_id'    => $user->id,
                'stage'      => $sendStage,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($inserted) {
                \App\Jobs\SendViolationWarningEmail::dispatch($user->id, $sendStage);
                \Log::info("Dispatched SendViolationWarningEmail job", ['user_id' => $user->id, 'stage' => $sendStage]);
            } else {
                \Log::info("Notification already exists; skipping dispatch", ['user_id' => $user->id, 'stage' => $sendStage]);
            }
        } else {
            \Log::warning("violation_notifications table missing — dispatching without DB guard", ['user_id' => $user->id, 'stage' => $sendStage]);
            \App\Jobs\SendViolationWarningEmail::dispatch($user->id, $sendStage);
            \Log::info("Dispatched SendViolationWarningEmail job (no notification table)", ['user_id' => $user->id, 'stage' => $sendStage]);
        }
    } catch (\Throwable $ex) {
        \Log::error("Error handling approval side-effects / dispatching job", [
            'violator_id' => $violatorId,
            'error' => $ex->getMessage(),
            'trace' => $ex->getTraceAsString()
        ]);
    }

    \Log::info("=== handleApprovalSideEffects END ===");
}
public function resetFormInputs()
{
    $this->description = null;
    $this->otherDescription = null;
    $this->license_plate = null;
    $this->violator = null;
    $this->area_id = null;
    $this->customArea = null;
    $this->evidence = null;
    $this->isUploadingEvidence = false;
    $this->violatorStatus = null;
    $this->violatorName = null;
    $this->violator_id = null;
}

    public function render()
    {
        return view('livewire.admin.create-violation-component', [
            'areas' => $this->areas,
        ]);
    }
}
