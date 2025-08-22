<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class ActivityLogComponent extends Component
{
    use WithPagination;

    public $search = '';
    public $actionFilter = '';   // Example: login, logout, update, etc.
    public $userType = '';       // '', 'student', 'employee'
    public $startDate = null;    // 'YYYY-MM-DD'
    public $endDate = null;      // 'YYYY-MM-DD'

    protected $queryString = [
        'search'       => ['except' => ''],
        'actionFilter' => ['except' => ''],
        'userType'     => ['except' => ''],
        'startDate'    => ['except' => null],
        'endDate'      => ['except' => null]
    ];

    public function updating($name, $value)
    {
        if (in_array($name, ['search','actionFilter','userType','startDate','endDate'])) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $logs = ActivityLog::with(['user' => function ($q) {
                $q->select('id','firstname','lastname','student_id','employee_id','profile_picture','department','program');
            }])
            // 🔍 SEARCH (action_type + description + user name/id)
            ->when($this->search !== '', function (Builder $q) {
                $s = trim($this->search);
                $q->where(function (Builder $sub) use ($s) {
                    $sub->where('action_type', 'like', "%{$s}%")
                        ->orWhere('description', 'like', "%{$s}%")
                        ->orWhereHas('user', function (Builder $u) use ($s) {
                            $u->where('firstname', 'like', "%{$s}%")
                              ->orWhere('lastname', 'like', "%{$s}%")
                              ->orWhere('student_id', 'like', "%{$s}%")
                              ->orWhere('employee_id', 'like', "%{$s}%");
                        });
                });
            })
            // 🎯 ACTION filter (login/logout/update/etc.)
            ->when($this->actionFilter !== '', fn (Builder $q) =>
                $q->where('action_type', $this->actionFilter)
            )
            // 👤 USER TYPE filter
            ->when($this->userType === 'student', fn (Builder $q) =>
                $q->whereHas('user', fn ($u) => $u->whereNotNull('student_id'))
            )
            ->when($this->userType === 'employee', fn (Builder $q) =>
                $q->whereHas('user', fn ($u) => $u->whereNotNull('employee_id'))
            )
            // 📅 DATE RANGE filter
            ->when($this->startDate, fn (Builder $q) =>
                $q->where('created_at', '>=', Carbon::parse($this->startDate)->startOfDay())
            )
            ->when($this->endDate, fn (Builder $q) =>
                $q->where('created_at', '<=', Carbon::parse($this->endDate)->endOfDay())
            )
            ->latest()
            ->paginate(10);

        return view('livewire.activity-log-component', [
            'activityLogs' => $logs,
        ]);
    }

    public function refreshLogs()
    {
        $this->resetPage();
    }
}
