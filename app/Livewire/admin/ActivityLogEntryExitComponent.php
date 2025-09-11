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
    public $actionFilter = '';   // Example: login, logout, update, etc.
    public $userType = '';       // '', 'student', 'employee'
    public $startDate = null;    // 'YYYY-MM-DD'
    public $endDate = null;      // 'YYYY-MM-DD'
    public $sortOrder = 'desc'; // default: newest first

    

    public function updating($name, $value)
    {
        if (in_array($name, ['search', 'actionFilter', 'userType', 'startDate', 'endDate'])) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $logs = ActivityLog::with([
            'user' => function ($q) {
                $q->select('id', 'firstname', 'lastname', 'student_id', 'employee_id', 'profile_picture', 'department', 'program');
            }
        ])
            // 👇 Only include entry/exit/denied_entry
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

            // 🎛 Action Filter (optional)
            ->when(
                $this->actionFilter !== '',
                fn(Builder $q) =>
                $q->where('action', $this->actionFilter)
            )

            ->when(
                $this->userType === 'student',
                fn(Builder $q) =>
                $q->whereHas('user', fn($u) => $u->whereNotNull('student_id'))
            )
            ->when(
                $this->userType === 'employee',
                fn(Builder $q) =>
                $q->whereHas('user', fn($u) => $u->whereNotNull('employee_id'))
            )

            ->when(
                $this->startDate,
                fn(Builder $q) =>
                $q->where('created_at', '>=', Carbon::parse($this->startDate)->startOfDay())
            )
            ->when(
                $this->endDate,
                fn(Builder $q) =>
                $q->where('created_at', '<=', Carbon::parse($this->endDate)->endOfDay())
            )
            ->orderBy('created_at', $this->sortOrder)
            ->paginate(10);


        return view('livewire.admin.activity-log-entry-exit-component', [
            'activityLogs' => $logs,
        ]);
    }


    public function refreshLogs()
    {
        $this->resetPage();
    }
}
