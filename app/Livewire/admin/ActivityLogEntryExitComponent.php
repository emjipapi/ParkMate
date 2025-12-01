<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\UnknownRfidLog;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use App\Models\ParkingArea;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
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
    public $reportType = 'day';

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
        // Base query for both logs and counts
        $query = ActivityLog::query()
            ->whereIn('action', ['entry', 'exit', 'denied_entry']);

        // Apply filters to the base query
        $this->applyFilters($query);

        // --- Calculate Counts ---
        $entryCount = (clone $query)->where('action', 'entry')->count();
        $exitCount = (clone $query)->where('action', 'exit')->count();
        $deniedCount = (clone $query)->where('action', 'denied_entry')->count();

        // --- Unknown Tags Count (independent query) ---
        $unknownTagsCount = UnknownRfidLog::query()
            ->when($this->startDate, fn ($q) => $q->where('created_at', '>=', Carbon::parse($this->startDate)->startOfDay()))
            ->when($this->endDate, fn ($q) => $q->where('created_at', '<=', Carbon::parse($this->endDate)->endOfDay()))
            ->count();

        // --- Fetch Paginated Logs ---
        $logsQuery = (clone $query)->with([
            'user:id,firstname,lastname,student_id,employee_id,profile_picture,department,program',
            'area'
        ]);

        // Apply search separately as it's complex and shouldn't affect counts
        $this->applySearch($logsQuery);

        // Apply sorting
        $secondaryDirection = $this->sortOrder === 'desc' ? 'desc' : 'asc';
        $logs = $logsQuery->orderBy('created_at', $this->sortOrder)
                         ->orderBy('id', $secondaryDirection)
                         ->paginate($this->perPage, ['*'], $this->pageName);

        return view('livewire.admin.activity-log-entry-exit-component', [
            'activityLogs' => $logs,
            'entryCount' => $entryCount,
            'exitCount' => $exitCount,
            'deniedCount' => $deniedCount,
            'unknownTagsCount' => $unknownTagsCount,
        ]);
    }

    /**
     * Apply all relevant filters to the query builder instance.
     *
     * @param Builder $query
     * @return void
     */
    private function applyFilters(Builder $query)
    {
        // Action Filter
        $query->when($this->actionFilter, fn (Builder $q) =>
            $q->where('action', $this->actionFilter)
        );

        // User Type Filter
        $query->when($this->userType, function (Builder $q) {
            $q->whereHas('user', function (Builder $userQuery) {
                if ($this->userType === 'student') {
                    $userQuery->whereNotNull('student_id')
                              ->where('student_id', '<>', '')
                              ->where('student_id', '<>', '0');
                } elseif ($this->userType === 'employee') {
                    $userQuery->whereNotNull('employee_id')
                              ->where('employee_id', '<>', '')
                              ->where('employee_id', '<>', '0')
                              ->where(fn ($sq) => $sq->whereNull('student_id')->orWhere('student_id', ''));
                }
            });
        });

        // Area Filter
        $query->when($this->areaFilter, function (Builder $q) {
            if ($this->areaFilter === 'null') {
                // Filter for null area_id (Main Gate)
                $q->whereNull('area_id');
            } else {
                // Filter for specific area
                $q->where('area_id', $this->areaFilter);
            }
        });

        // Date Range Filter
        $query->when($this->startDate, fn (Builder $q) =>
            $q->where('created_at', '>=', Carbon::parse($this->startDate)->startOfDay())
        );
        $query->when($this->endDate, fn (Builder $q) =>
            $q->where('created_at', '<=', Carbon::parse($this->endDate)->endOfDay())
        );
    }

    /**
     * Apply search logic to the query.
     *
     * @param Builder $query
     * @return void
     */
    private function applySearch(Builder $query)
    {
        $query->when($this->search, function (Builder $q) {
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
        });
    }

    /**
     * Triggered by the modal's Generate button.
     * Builds start/end based on reportType or custom inputs and redirects to controller route
     * which streams the PDF download (since Livewire XHR can't reliably download files).
     */
public function generateReport()
{
    // Compute start/end strings based on report type
    if ($this->reportType === 'day') {
        $start = $end = Carbon::now()->format('Y-m-d');
    } elseif ($this->reportType === 'week') {
        $start = Carbon::now()->startOfWeek()->format('Y-m-d');
        $end   = Carbon::now()->endOfWeek()->format('Y-m-d');
    } elseif ($this->reportType === 'month') {
        $start = Carbon::now()->startOfMonth()->format('Y-m-d');
        $end   = Carbon::now()->endOfMonth()->format('Y-m-d');
    } else {
        // Custom range: validate dates
        if (!$this->reportStartDate || !$this->reportEndDate) {
            $this->dispatchBrowserEvent('notify', [
                'type' => 'error',
                'message' => 'Please select a start and end date for custom range.'
            ]);
            return;
        }

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

    // Log activity
    ActivityLog::create([
        'actor_type' => 'admin',
        'actor_id'   => Auth::guard('admin')->id(),
        'area_id'    => null,
        'action'     => 'generate_report',
        'details'    => 'Admin ' 
            . Auth::guard('admin')->user()->firstname . ' ' . Auth::guard('admin')->user()->lastname
            . ' generated an attendance report for the period ' . $start . ' to ' . $end . '.',
        'created_at' => now(),
    ]);

    // Redirect to report download
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
