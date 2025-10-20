<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Violation;
use App\Models\Vehicle;
use App\Models\Admin;
use App\Models\User; // add any other reporter models you use
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
class ForEndorsementComponent extends Component
{
    use WithPagination;
public $search = '';
public $reporterType = '';
public $startDate = null;
public $endDate = null;
public $sortOrder = 'desc';
    public $violationsActionTaken = [];
    public $activeTab = 'pending';
    public $vehicles = [];
    public $users = [];
    protected $paginationTheme = 'bootstrap';
        public $pageName = 'endorsementPage';

    // Search properties for dynamic loading
    public $vehicleSearch = '';
    public $userSearch = '';

    // Limits
    protected $vehicleLimit = 3;
    protected $userLimit = 3;

    public $searchTerm = '';
    public $searchResults = [];
    
public $endorsementReportType = 'day';
public $endorsementReportStartDate = null;
public $endorsementReportEndDate = null;
    public $perPage = 15; // default
    public $perPageOptions = [15, 25, 50, 100];
    public $evidence;
public $compressedEvidence;


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
public function generateEndorsementReport()
{
    // Validate custom range dates if needed
    if ($this->endorsementReportType === 'range') {
        $this->validate([
            'endorsementReportStartDate' => 'required|date',
            'endorsementReportEndDate' => 'required|date|after_or_equal:endorsementReportStartDate',
        ]);
    }

    // compute start/end as Y-m-d strings
    if ($this->endorsementReportType === 'day') {
        $start = $end = Carbon::now()->toDateString();
    } elseif ($this->endorsementReportType === 'week') {
        $start = Carbon::now()->startOfWeek()->toDateString();
        $end   = Carbon::now()->endOfWeek()->toDateString();
    } elseif ($this->endorsementReportType === 'month') {
        $start = Carbon::now()->startOfMonth()->toDateString();
        $end   = Carbon::now()->endOfMonth()->toDateString();
    } else {
        // custom range
        try {
            $start = Carbon::parse($this->endorsementReportStartDate)->toDateString();
            $end   = Carbon::parse($this->endorsementReportEndDate)->toDateString();
        } catch (\Exception $e) {
            session()->flash('error', 'Invalid date format.');
            return;
        }
    }
        ActivityLog::create([
        'actor_type' => 'admin',
        'actor_id'   => Auth::guard('admin')->id(),
        'area_id'    => null, // or set a relevant area if applicable
        'action'     => 'generate_report',
        'details'    => 'Admin ' 
            . Auth::guard('admin')->user()->firstname . ' ' . Auth::guard('admin')->user()->lastname
            . ' generated an endorsement report for the period ' . $start . ' to ' . $end . '.',
        'created_at' => now(),
    ]);

    // Use Livewire's redirect method
return $this->redirect(route('reports.endorsement', [
    'startDate' => $start,
    'endDate'   => $end,
]));
}
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

    /**
     * Find license plates by violator ID
     */
    private function findPlatesByViolator($violatorId)
    {
        $vehicles = Vehicle::where('user_id', $violatorId)->pluck('license_plate')->toArray();
        return $vehicles ? ['plates' => $vehicles] : null;
    }

public function render()
{
    // Base query: only for_endorsement status
    $violationsQuery = Violation::with(['reporter', 'area', 'violator'])
        ->where('status', 'for_endorsement');

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
    $violationsQuery->when(($this->reporterType ?? '') === 'student', fn ($q) =>
        $q->whereHas('reporter', fn ($u) =>
            $u->whereNotNull('student_id')
              ->where('student_id', '<>', '')
              ->where('student_id', '<>', '0')
        )
    );

    $violationsQuery->when(($this->reporterType ?? '') === 'employee', fn ($q) =>
        $q->whereHas('reporter', fn ($u) =>
            $u->whereNotNull('employee_id')
              ->where('employee_id', '<>', '')
              ->where('employee_id', '<>', '0')
              ->where(function ($q) {
                  $q->whereNull('student_id')->orWhere('student_id', '');
              })
        )
    );

    // Date range filters
    $violationsQuery->when($this->startDate ?? null, fn ($q) =>
        $q->where('created_at', '>=', Carbon::parse($this->startDate)->startOfDay())
    );
    $violationsQuery->when($this->endDate ?? null, fn ($q) =>
        $q->where('created_at', '<=', Carbon::parse($this->endDate)->endOfDay())
    );

    // Ordering + pagination
    $violations = $violationsQuery
        ->orderBy('created_at', ($this->sortOrder ?? 'desc') === 'asc' ? 'asc' : 'desc')
        ->paginate(1, ['*'], $this->pageName);
    // Process violations for display
    // $violations = $violationsQuery
    // ->orderBy('created_at', ($this->sortOrder ?? 'desc') === 'asc' ? 'asc' : 'desc')
    // ->paginate($this->perPage);

// --- DEBUGGING + DEFENSIVE FALLBACK (paste this block) ---


$violations->getCollection()->transform(function ($v) {
    $rt = $v->reporter_type ?? '';
    // Log a visible marker, length and whether PHP knows the class
    Log::info('for-endorsement: reporter_type check', [
        'violation_id' => $v->id,
        'marker' => 'INSERT("' . $rt . '")',
        'len' => is_string($rt) ? strlen($rt) : null,
        'hex_bytes' => is_string($rt) ? implode(' ', array_map(fn($b) => sprintf('%02x', $b), unpack('C*', $rt))) : null,
        'class_exists_exact' => is_string($rt) ? class_exists($rt) : false,
    ]);

    // Defensive: trim then try to safely load/report relation if relation missing
    $trimmed = is_string($rt) ? trim($rt) : $rt;
    if ($trimmed !== $rt) {
        // update in-memory value (not DB) so later code uses trimmed version
        $v->reporter_type = $trimmed;
        Log::info('for-endorsement: trimmed reporter_type', [
            'violation_id' => $v->id,
            'before' => $rt,
            'after' => $trimmed,
        ]);
    }

    if (! $v->reporter && !empty($trimmed) && is_string($trimmed)) {
        $class = $trimmed;

        // If saved value is short key like 'admin' try App\Models\Admin
        if (! class_exists($class)) {
            $candidate = 'App\\Models\\' . ucfirst($class);
            if (class_exists($candidate)) {
                $class = $candidate;
            }
        }

        if (class_exists($class)) {
            try {
                $modelQuery = method_exists($class, 'withTrashed') ? $class::withTrashed() : $class;
                $found = $modelQuery::find($v->reporter_id);
                if ($found) {
                    // attach relation so blade can access properties safely
                    $v->setRelation('reporter', $found);
                    Log::info('for-endorsement: reporter fallback attached', [
                        'violation_id' => $v->id,
                        'attached_class' => $class,
                        'reporter_id' => $v->reporter_id,
                    ]);
                } else {
                    Log::info('for-endorsement: reporter fallback not found', [
                        'violation_id' => $v->id,
                        'tried_class' => $class,
                        'reporter_id' => $v->reporter_id,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('for-endorsement: reporter fallback error', [
                    'violation_id' => $v->id,
                    'class' => $class,
                    'error' => $e->getMessage(),
                ]);
            }
        } else {
            Log::warning('for-endorsement: class does not exist', [
                'violation_id' => $v->id,
                'attempted' => [$trimmed, 'App\\Models\\'.ucfirst($trimmed)],
            ]);
        }
    }

    return $v;
});

    return view('livewire.admin.for-endorsement-component', [
        'violations' => $violations,
        'vehicles' => $this->vehicles,
        'users' => $this->users
    ]);
}
}