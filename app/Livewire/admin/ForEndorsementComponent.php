<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Violation;
use App\Models\Vehicle;
use App\Models\User;
use Carbon\Carbon;
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

    // Search properties for dynamic loading
    public $vehicleSearch = '';
    public $userSearch = '';

    // Limits
    protected $vehicleLimit = 3;
    protected $userLimit = 3;

    public $searchTerm = '';
    public $searchResults = [];
    
public $endorsementReportType = 'week';
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
    if ($this->endorsementReportType === 'week') {
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

    // SEARCH: license_plate, description, reporter, violator
    $violationsQuery->when(trim($this->search ?? '') !== '', function ($q) {
        $s = trim($this->search);
        $q->where(function ($sub) use ($s) {
            $sub->where('license_plate', 'like', "%{$s}%")
                ->orWhere('description', 'like', "%{$s}%")
                ->orWhereHas('reporter', function ($r) use ($s) {
                    $r->where('firstname', 'like', "%{$s}%")
                      ->orWhere('lastname', 'like', "%{$s}%")
                      ->orWhere('student_id', 'like', "%{$s}%")
                      ->orWhere('employee_id', 'like', "%{$s}%")
                      ->orWhereRaw("CONCAT(firstname, ' ', lastname) like ?", ["%{$s}%"]);
                })
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
        ->paginate($this->perPage);

    // Process violations for display
    $violations->getCollection()->transform(function ($violation) {
        // Populate missing violator_id from license_plate
        if (empty($violation->violator_id) && !empty($violation->license_plate)) {
            $match = $this->findViolatorByPlate($violation->license_plate);
            if ($match) {
                $violation->violator_id = $match['user_id'];
                $violation->save();
            }
        }

        // Populate missing license_plate from violator_id
        if (!empty($violation->violator_id) && empty($violation->license_plate)) {
            $match = $this->findPlatesByViolator($violation->violator_id);
            if ($match && !empty($match['plates'])) {
                $violation->license_plate = $match['plates'][0];
                $violation->save();
            }
        }

        // Add virtual property for the view
        $violation->violator_name = $violation->violator
            ? trim($violation->violator->firstname . ' ' . $violation->violator->lastname)
            : 'Unknown';

        return $violation;
    });

    return view('livewire.admin.for-endorsement-component', [
        'violations' => $violations,
        'vehicles' => $this->vehicles,
        'users' => $this->users
    ]);
}
}