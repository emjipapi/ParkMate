<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class ActivityLogEntryExitComponent extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public $search = '';
    public $actionFilter = '';
    public $userType = '';      
    public $startDate = null;   
    public $endDate = null;     
    public $sortOrder = 'desc'; 

    // 🚫 Don’t sync anything to the query string
    protected $queryString = [];

    // 👇 Custom page name (so Livewire doesn’t inject ?page=2 into the URL)
    protected $pageName = 'entryExitPage';

    public function updating($name, $value)
    {
        if (in_array($name, ['search','actionFilter','userType','startDate','endDate'])) {
            $this->resetPage($this->pageName);
        }
    }

    public function render()
    {
        $logs = ActivityLog::with([
                'user' => function ($q) {
                    $q->select('id', 'firstname', 'lastname', 'student_id', 'employee_id', 'profile_picture', 'department', 'program');
                }
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

            // 👤 User Type
            ->when($this->userType === 'student', fn (Builder $q) =>
                $q->whereHas('user', fn ($u) => $u->whereNotNull('student_id'))
            )
            ->when($this->userType === 'employee', fn (Builder $q) =>
                $q->whereHas('user', fn ($u) => $u->whereNotNull('employee_id'))
            )

            // 📅 Date Range
            ->when($this->startDate, fn (Builder $q) =>
                $q->where('created_at', '>=', Carbon::parse($this->startDate)->startOfDay())
            )
            ->when($this->endDate, fn (Builder $q) =>
                $q->where('created_at', '<=', Carbon::parse($this->endDate)->endOfDay())
            )

            ->orderBy('created_at', $this->sortOrder)
            ->paginate(10, ['*'], $this->pageName); // 👈 custom page name

        return view('livewire.admin.activity-log-entry-exit-component', [
            'activityLogs' => $logs,
        ]);
    }

    public function refreshLogs()
    {
        $this->resetPage($this->pageName);
    }
}
