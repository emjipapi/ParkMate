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

    public $vehicles = [];

    protected $paginationTheme = 'bootstrap';

    public $perPage = 15; // default

    public $perPageOptions = [15, 25, 50, 100];

    public $pageName = 'approvedPage';

    public $proofs = [];

    // reset page when perPage changes
    public function updatedPerPage()
    {
        // explicitly reset the default "page" paginator
        $this->resetPage($this->pageName);
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

// student reporters
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

// EMPLOYEE reporters
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
// Admin reporters
$violationsQuery->when($this->reporterType === 'admin', fn (Builder $q) =>
    $q->whereHasMorph('reporter', [Admin::class], fn (Builder $a) => $a->whereNotNull('admin_id'))
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
        // This is just a placeholder - file upload handling happens in markForEndorsement
    }




    public function proceedToResolution($violationId)
    {
        $violation = Violation::find($violationId);
        if (! $violation) {
            return;
        }

        // Validate the image upload
        $this->validate([
            "proofs.{$violationId}" => 'required|image|mimes:jpg,jpeg,png|max:10240',
        ], [
            "proofs.{$violationId}.required" => 'Please upload an image before proceeding.',
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

        // Store the uploaded image directly
        if (isset($this->proofs[$violationId])) {
            $evidence['approved'] = $this->proofs[$violationId]->store('evidence/approved', 'public');
        }

        // Auto-assign status based on violator's violation history
        $newStatus = 'first_violation'; // default
        
        if ($violation->violator_id) {
            $violationCount = Violation::where('violator_id', $violation->violator_id)
                ->whereIn('status', ['first_violation', 'second_violation', 'third_violation'])
                ->where('id', '!=', $violation->id)
                ->count();
            
            if ($violationCount === 1) {
                $newStatus = 'second_violation';
            } elseif ($violationCount >= 2) {
                $newStatus = 'third_violation';
            }
        }

        // Save evidence properly respecting model casts
        $casts = $violation->getCasts();
        if (isset($casts['evidence']) && $casts['evidence'] === 'array') {
            $violation->evidence = $evidence;
        } else {
            $violation->evidence = json_encode($evidence);
        }

        // Update status to the appropriate violation level
        $violation->status = $newStatus;
        $violation->save();

        // optional: create activity log entry for audit
        ActivityLog::create([
            'actor_type' => 'admin',
            'actor_id'   => Auth::guard('admin')->id(),
            'area_id'    => $violation->area_id,
            'action'     => 'resolve',
            'details'    => 'Admin '
                . Auth::guard('admin')->user()->firstname . ' ' . Auth::guard('admin')->user()->lastname
                . ' moved violation #' . $violation->id . ' to resolution with status: ' . $newStatus . '.',
            'created_at' => now(),
        ]);

        // clear the temporary file in Livewire so UI resets
        if (isset($this->proofs[$violationId])) {
            unset($this->proofs[$violationId]);
        }

        // clear validation for that input
        $this->resetValidation("proofs.$violationId");

        // Reset pagination (optional)
        $this->resetPage($this->pageName);

        session()->flash('message', 'Violation moved to resolution with status: ' . $newStatus . '.');
    }

    // Add method if it doesn't exist
    private function findPlatesByViolator($violatorId)
    {
        $vehicles = Vehicle::where('user_id', $violatorId)->pluck('license_plate')->toArray();

        return $vehicles ? ['plates' => $vehicles] : null;
    }
    // Reset pagination when any filter changes
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
