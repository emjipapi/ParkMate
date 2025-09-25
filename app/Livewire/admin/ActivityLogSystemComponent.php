<?php

namespace App\Livewire\Admin;

use App\Models\ActivityLog;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class ActivityLogSystemComponent extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public $search = '';

    public $actionFilter = '';

    public $userType = '';

    public $startDate = null;

    public $endDate = null;

    public $sortOrder = 'desc';

    // 🚫 No query string syncing at all
    protected $queryString = [];

    // 👇 Give pagination a "custom name" that won't appear in the URL
    protected $pageName = 'activityLogsPage';

    public $perPage = 15; // default

    public $perPageOptions = [15, 25, 50, 100];

    // reset page when perPage changes
    public function updatedPerPage()
    {
        // explicitly reset the default "page" paginator
        $this->resetPage('page');
    }

    public function updating($name, $value)
    {
        if (in_array($name, ['search', 'actionFilter', 'userType', 'startDate', 'endDate'])) {
            $this->resetPage($this->pageName);
        }
    }

    public function render()
    {
        $logs = ActivityLog::with(['user' => function ($q) {
            $q->select('id', 'firstname', 'lastname', 'student_id', 'employee_id', 'profile_picture', 'department', 'program');
        }])
            ->whereNotIn('action', ['entry', 'exit', 'denied_entry'])

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

            // ACTION FILTER
            ->when($this->actionFilter !== '', fn (Builder $q) => $q->where('action', $this->actionFilter)
            )

// USER TYPE
            ->when($this->userType === 'student', fn (Builder $q) => $q->where('actor_type', 'user')
                ->whereHas('user', fn ($u) => $u->whereNotNull('student_id')
                    ->where('student_id', '<>', '')
                    ->where('student_id', '<>', '0')
                )
            )
            ->when($this->userType === 'employee', fn (Builder $q) => $q->where('actor_type', 'user')
                ->whereHas('user', fn ($u) => $u->whereNotNull('employee_id')
                    ->where('employee_id', '<>', '')
                    ->where('employee_id', '<>', '0')
                    ->where(function ($q) {
                        $q->whereNull('student_id')->orWhere('student_id', '');
                    })
                )
            )
            ->when($this->userType === 'admin', fn (Builder $q) => $q->where('actor_type', 'admin')
            )

            // 📅 DATE RANGE
            ->when($this->startDate, fn (Builder $q) => $q->where('created_at', '>=', Carbon::parse($this->startDate)->startOfDay())
            )
            ->when($this->endDate, fn (Builder $q) => $q->where('created_at', '<=', Carbon::parse($this->endDate)->endOfDay())
            )

            ->orderBy('created_at', $this->sortOrder)
            ->paginate($this->perPage, ['*'], $this->pageName); // 👈 force custom name

        return view('livewire.admin.activity-log-system-component', [
            'activityLogs' => $logs,
        ]);
    }

    public function refreshLogs()
    {
        $this->resetPage($this->pageName);
    }
}
