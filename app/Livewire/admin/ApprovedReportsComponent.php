<?php

namespace App\Livewire\Admin;

use App\Models\ActivityLog;
use App\Models\User;
use App\Models\Admin;
use App\Models\Vehicle;
use App\Models\Violation;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class ApprovedReportsComponent extends Component
{
    use WithFileUploads;
    use WithPagination;

    // UI / filters
    public $search = '';

    public $reporterType = '';     // '' | 'student' | 'employee' | 'admin'

    public $startDate = null;

    public $endDate = null;

    public $sortOrder = 'desc';    // 'desc' or 'asc'

    public $violationsActionTaken = [];

    public $vehicles = [];

    protected $paginationTheme = 'bootstrap';

    public $perPage = 15; // default

    public $perPageOptions = [15, 25, 50, 100];

    public $pageName = 'approvedPage';

    public $compressedProofs = []; // store compressed versions per violation
    public $proofs = [];

    // reset page when perPage changes
    public function updatedPerPage()
    {
        // explicitly reset the default "page" paginator
        $this->resetPage('page');
    }

    public function mount()
    {
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
                    'owner_name' => $vehicle->user ? $vehicle->user->firstname.' '.$vehicle->user->lastname : null,
                ];
            });
    }

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
                'owner_name' => trim($vehicle->user->firstname.' '.$vehicle->user->lastname),
                'license_plate' => $vehicle->license_plate,
                'vehicle_id' => $vehicle->id,
            ];
        }

        return null;
    }

    public function render()
    {
        // base query: only approved
        $violationsQuery = Violation::with(['reporter', 'area', 'violator'])
            ->where('status', 'approved');

        // SEARCH: license_plate, description, reporter, violator
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

        // Reporter type filters (student / employee)
        $violationsQuery->when($this->reporterType === 'student', fn (Builder $q) => $q->whereHas('reporter', fn (Builder $u) => $u->whereNotNull('student_id')
            ->where('student_id', '<>', '')
            ->where('student_id', '<>', '0')
        )
        );

        $violationsQuery->when($this->reporterType === 'employee', fn (Builder $q) => $q->whereHas('reporter', fn (Builder $u) => $u->whereNotNull('employee_id')
            ->where('employee_id', '<>', '')
            ->where('employee_id', '<>', '0')
            ->where(function ($q) {
                $q->whereNull('student_id')->orWhere('student_id', '');
            })
        )
        );

        // Date range filters
        $violationsQuery->when($this->startDate, fn (Builder $q) => $q->where('created_at', '>=', Carbon::parse($this->startDate)->startOfDay())
        );
        $violationsQuery->when($this->endDate, fn (Builder $q) => $q->where('created_at', '<=', Carbon::parse($this->endDate)->endOfDay())
        );

        // Ordering + pagination (preserve your perPage)
        $violations = $violationsQuery
            ->orderBy('created_at', $this->sortOrder === 'asc' ? 'asc' : 'desc')
            ->paginate($this->perPage, ['*'], $this->pageName);

        // --- Keep your approved-specific processing (populate missing relations and add violator_name) ---
        $violations->getCollection()->transform(function ($violation) {
            // Populate missing violator_id from license_plate
            if (empty($violation->violator_id) && ! empty($violation->license_plate)) {
                $match = $this->findViolatorByPlate($violation->license_plate);
                if ($match) {
                    $violation->violator_id = $match['user_id'];
                    $violation->save();
                }
            }

            // Populate missing license_plate from violator_id
            if (! empty($violation->violator_id) && empty($violation->license_plate)) {
                $match = $this->findPlatesByViolator($violation->violator_id);
                if ($match && ! empty($match['plates'])) {
                    $violation->license_plate = $match['plates'][0];
                    $violation->save();
                }
            }

            // Add virtual property for the view
            $violation->violator_name = $violation->violator
                ? trim($violation->violator->firstname.' '.$violation->violator->lastname)
                : 'Unknown';

            return $violation;
        });

        return view('livewire.admin.approved-reports-component', [
            'violations' => $violations,
        ]);
    }

    /**
     * Mark a violation as ForEndorsement, optionally store approved image and action_taken.
     * Uses $this->proofs[$violationId] as upload from the row's file input.
     */
public function updatedProofs($value, $name)
{
    \Log::info('updatedProofs called', [
        'raw_name' => $name,
        'value_type' => is_object($value) ? get_class($value) : gettype($value),
    ]);

    // Get violation id: support "51" or "proofs.51"
    if (is_numeric($name)) {
        $violationId = (int) $name;
    } elseif (preg_match('/^proofs\.(\d+)/', $name, $m)) {
        $violationId = (int) $m[1];
    } else {
        $violationId = null;
    }

    if (! $violationId) {
        \Log::warning('updatedProofs: could not determine violationId', ['name' => $name]);
        return;
    }

    // Get the file object
    $file = null;
    if (is_object($value) && method_exists($value, 'getPathname')) {
        $file = $value;
    } elseif (isset($this->proofs[$violationId]) && is_object($this->proofs[$violationId]) && method_exists($this->proofs[$violationId], 'getPathname')) {
        $file = $this->proofs[$violationId];
    }

    if (! $file) {
        \Log::warning("updatedProofs: no usable upload object found for violation {$violationId}");
        return;
    }

    try {
        \Log::info("📥 Proof upload detected for violation {$violationId} — dispatching compression job...");

        // Generate deterministic filename
        $hash = substr(md5(uniqid((string) mt_rand(), true)), 0, 8);
        $filename = "proof_{$violationId}_{$hash}.jpg";

        // Store original to local disk temporarily
        $tmpOriginalPath = 'evidence/uploads/originals/' . $filename;
        $file->storeAs('evidence/uploads/originals', $filename, 'local');

        // Set expected compressed path
        $compressedPath = 'evidence/tmp/' . $filename;
        $this->compressedProofs[$violationId] = $compressedPath;

        \Log::info('⚙️ Compression job dispatched', [
            'violation_id' => $violationId,
            'input' => $tmpOriginalPath,
            'output' => $compressedPath
        ]);

        // Dispatch job
        \App\Jobs\ProcessEvidenceImage::dispatch(
            'local',              // input disk
            $tmpOriginalPath,     // input path
            'public',             // output disk
            [$compressedPath],    // output paths array
            1200,                 // maxWidth
            1200,                 // maxHeight
            90                    // quality
        );

    } catch (\Exception $e) {
        \Log::error("❌ Failed to dispatch proof compression job for violation {$violationId}: " . $e->getMessage());
        $this->compressedProofs[$violationId] = null;
        session()->flash('error', 'Failed to process the uploaded image. Please try again.');
    }
}




    public function markForEndorsement($violationId)
    {
        $violation = Violation::find($violationId);
        if (! $violation) {
            return;
        }

        // validate both the optional approved image and required action
        $this->validate([
            "proofs.{$violationId}" => 'required|image|mimes:jpg,jpeg,png|max:10240',
            "violationsActionTaken.{$violationId}" => 'required|string|min:1',
        ], [
            "proofs.{$violationId}.required" => 'Please upload an image before proceeding.',
            "violationsActionTaken.{$violationId}.required" => 'Please select an action before proceeding.',
        ]);

        // Load existing evidence safely (support casted array or raw JSON/string)
        $existing = $violation->evidence;
        if (is_string($existing) && $existing !== '') {
            $decoded = @json_decode($existing, true);
            $evidence = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : [];
        } elseif (is_array($existing)) {
            $evidence = $existing;
        } else {
            $evidence = [];
        }

        // If there's an uploaded approved image for this violation, store it
if (! empty($this->compressedProofs[$violationId])) {
    $tmpPath = $this->compressedProofs[$violationId];
    
    // Wait a moment and check if file exists
    if (Storage::disk('public')->exists($tmpPath)) {
        $finalPath = str_replace('tmp/', 'approved/', $tmpPath);
        Storage::disk('public')->move($tmpPath, $finalPath);
        $evidence['approved'] = $finalPath;
    } else {
        \Log::warning("Compressed proof not ready yet for violation {$violationId}");
        session()->flash('error', 'Image is still processing. Please wait a moment and try again.');
        return;
    }
}

        // Save action taken if provided
        $actionTaken = $this->violationsActionTaken[$violationId] ?? null;
        if ($actionTaken) {
            $violation->action_taken = $actionTaken;
        }

        // Save evidence properly respecting model casts
        $casts = $violation->getCasts();
        if (isset($casts['evidence']) && $casts['evidence'] === 'array') {
            $violation->evidence = $evidence;
        } else {
            $violation->evidence = json_encode($evidence);
        }

        $violation->markForEndorsement();
        // determine admin user using the admin guard
        $admin = Auth::guard('admin')->user();

        // If you require an authenticated admin, you can abort or return with an error:
        // if (! $admin) { abort(403, 'Admin not authenticated'); }

        $adminName = $admin ? ($admin->firstname ?? $admin->name ?? 'Admin#'.$admin->id) : 'System';
        $admin = Auth::guard('admin')->user();

        // optional: create activity log entry for audit
ActivityLog::create([
    'actor_type' => 'admin',
    'actor_id'   => Auth::guard('admin')->id(),
    'area_id'    => $violation->area_id,
    'action'     => 'resolve',
    'details'    => 'Admin '
        . Auth::guard('admin')->user()->firstname . ' ' . Auth::guard('admin')->user()->lastname
        . ' marked violation #' . $violation->id . ' for endorsement.',
    'created_at' => now(),
]);


        // clear the temporary file in Livewire so UI resets
        if (isset($this->proofs[$violationId])) {
            unset($this->proofs[$violationId]);
        }

        // clear validation for that input
        $this->resetValidation("proofs.$violationId");

        // Reset pagination (optional)
        $this->resetPage();

        session()->flash('message', 'Violation marked as for endorsement and evidence attached.');
    }

    // Add method if it doesn't exist
    private function findPlatesByViolator($violatorId)
    {
        $vehicles = Vehicle::where('user_id', $violatorId)->pluck('license_plate')->toArray();

        return $vehicles ? ['plates' => $vehicles] : null;
    }
}
