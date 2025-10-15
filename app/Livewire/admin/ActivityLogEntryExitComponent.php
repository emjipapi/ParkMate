<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use App\Models\ParkingArea;

class ActivityLogEntryExitComponent extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public $search = '';
    public $actionFilter = '';
    public $userType = '';

    // page filters (used for the on-page table)
    public $startDate = null;
    public $endDate = null;

    // report-specific props (separate from the page filters)
    public $reportStartDate = null;
    public $reportEndDate = null;
    public $reportType = 'week';

    public $sortOrder = 'desc';

    // 🚫 Don’t sync anything to the query string
    protected $queryString = [];

    // 👇 Custom page name (so Livewire doesn’t inject ?page=2 into the URL)
    protected $pageName = 'entryExitPage';

        public $perPage = 15; // default
    public $perPageOptions = [15, 25, 50, 100];
    public $areaFilter = '';
    public $parkingAreas = [];

    // reset page when perPage changes
public function updatedPerPage()
{
    // explicitly reset the default "page" paginator
    $this->resetPage('page');
}

    public function updating($name, $value)
    {
        // only reset page when user changes the on-page filters (not report fields)
        if (in_array($name, ['search','actionFilter','userType','startDate','endDate', 'areaFilter'])) {
            $this->resetPage($this->pageName);
        }
    }

    public function render()
    {
        $logs = ActivityLog::with([
                'user' => function ($q) {
                    $q->select('id', 'firstname', 'lastname', 'student_id', 'employee_id', 'profile_picture', 'department', 'program');
                },
                'area'
            ])
            // ✅ Only include entry/exit/denied_entry
            ->whereIn('action', ['entry', 'exit', 'denied_entry'])

            // 🔍 SEARCH
            ->when($this->search !== '', function (Builder $q) {
                $s = trim($this->search);
                $q->where(function (Builder $sub) use ($s) {
                    $sub->where('action', 'like', "%{$s}%")
                        ->orWhere('details', 'like', "%{$s}%")
                        ->orWhereHas('user', function (Builder $u) use ($s) {
                            $u->where('firstname', 'like', "%{$s}%")
                              ->orWhere('lastname', 'like', "%{$s}%")
                              ->orWhere('student_id', 'like', "%{$s}%")
                              ->orWhere('employee_id', 'like', "%{$s}%");
                        });
                });
            })

            // 🎛 Action Filter
            ->when($this->actionFilter !== '', fn (Builder $q) =>
                $q->where('action', $this->actionFilter)
            )
// USER TYPE
->when($this->userType === 'student', fn (Builder $q) =>
    $q->where('actor_type', 'user')
      ->whereHas('user', fn ($u) =>
          $u->whereNotNull('student_id')
             ->where('student_id', '<>', '')
             ->where('student_id', '<>', '0')
      )
)
            // AREA FILTER
            ->when($this->areaFilter !== '', fn (Builder $q) =>
                $q->where('area_id', $this->areaFilter) // 👈 --- ADD THIS
            )
->when($this->userType === 'employee', fn (Builder $q) =>
    $q->where('actor_type', 'user')
      ->whereHas('user', fn ($u) =>
          $u->whereNotNull('employee_id')
             ->where('employee_id', '<>', '')
             ->where('employee_id', '<>', '0')
             ->where(function ($q) {
                 $q->whereNull('student_id')->orWhere('student_id', '');
             })
      )
)


            // 📅 Date Range (on-page filters)
            ->when($this->startDate, fn (Builder $q) =>
                $q->where('created_at', '>=', Carbon::parse($this->startDate)->startOfDay())
            )
          ->when($this->endDate, fn (Builder $q) =>
    $q->where('created_at', '<=', Carbon::parse($this->endDate)->endOfDay())
);

$secondaryDirection = $this->sortOrder === 'desc' ? 'desc' : 'asc';

$logs = $logs->orderBy('created_at', $this->sortOrder)
             ->orderBy('id', $secondaryDirection)
             ->paginate($this->perPage, ['*'], $this->pageName);

        return view('livewire.admin.activity-log-entry-exit-component', [
            'activityLogs' => $logs,
        ]);
    }

    /**
     * Triggered by the modal's Generate button.
     * Builds start/end based on reportType or custom inputs and redirects to controller route
     * which streams the PDF download (since Livewire XHR can't reliably download files).
     */
    public function generateReport()
    {
        // compute start/end strings to pass to the controller route
        if ($this->reportType === 'week') {
            $start = Carbon::now()->startOfWeek()->format('Y-m-d');
            $end   = Carbon::now()->endOfWeek()->format('Y-m-d');
        } elseif ($this->reportType === 'month') {
            $start = Carbon::now()->startOfMonth()->format('Y-m-d');
            $end   = Carbon::now()->endOfMonth()->format('Y-m-d');
        } else {
            // custom: validate you have values
            if (!$this->reportStartDate || !$this->reportEndDate) {
                $this->dispatchBrowserEvent('notify', [
                    'type' => 'error',
                    'message' => 'Please select a start and end date for custom range.'
                ]);
                return;
            }

            // ensure valid date order
            try {
                $s = Carbon::parse($this->reportStartDate)->startOfDay();
                $e = Carbon::parse($this->reportEndDate)->endOfDay();
            } catch (\Exception $ex) {
                $this->dispatchBrowserEvent('notify', [
                    'type' => 'error',
                    'message' => 'Invalid dates provided.'
                ]);
                return;
            }

            if ($s->gt($e)) {
                $this->dispatchBrowserEvent('notify', [
                    'type' => 'error',
                    'message' => 'Start date must be before or equal to end date.'
                ]);
                return;
            }

            $start = $s->format('Y-m-d');
            $end   = $e->format('Y-m-d');
        }

        // Redirect to the controller route to trigger file download
        return redirect()->route('reports.attendance', [
            'reportType' => $this->reportType,
            'startDate'  => $start,
            'endDate'    => $end,
        ]);
    }

    /**
     * Optional helper: reset the report modal inputs.
     * Call this before opening the modal to clear prior selections.
     */
    public function resetReportInputs()
    {
        $this->reportType = 'week';
        $this->reportStartDate = null;
        $this->reportEndDate = null;
    }
        public function mount()
    {
        // 👈 --- ADD THIS: Fetch all parking areas so the dropdown can be built
        $this->parkingAreas = ParkingArea::orderBy('name')->get();
    }

    public function refreshLogs()
    {
        $this->resetPage($this->pageName);
    }
}
