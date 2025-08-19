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

    // If you prefer to use {{ $activityLogs->links() }} without specifying a view:
    // protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $statusFilter = '';   // '', 'IN', 'OUT'  (⚠ your enum permits only IN/OUT)
    public $userType = '';       // '', 'student', 'employee'
    public $startDate = null;    // 'YYYY-MM-DD'
    public $endDate = null;      // 'YYYY-MM-DD'

    // Optional: keep filters in URL
    protected $queryString = [
        'search'       => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'userType'     => ['except' => ''],
        'startDate'    => ['except' => null],
        'endDate'      => ['except' => null]
        
    ];

    // Reset pagination whenever a filter/search changes
    public function updating($name, $value)
    {
        if (in_array($name, ['search','statusFilter','userType','startDate','endDate'])) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $logs = ActivityLog::with(['user' => function ($q) {
                $q->select('id','firstname','lastname','student_id','employee_id','profile_picture','department','program');
            }])
            // SEARCH (rfid + user name + student/employee id)
            ->when($this->search !== '', function (Builder $q) {
                $s = trim($this->search);
                $q->where(function (Builder $sub) use ($s) {
                    $sub->where('rfid_tag', 'like', "%{$s}%")
                        ->orWhereHas('user', function (Builder $u) use ($s) {
                            $u->where('firstname', 'like', "%{$s}%")
                              ->orWhere('lastname', 'like', "%{$s}%")
                              ->orWhere('student_id', 'like', "%{$s}%")
                              ->orWhere('employee_id', 'like', "%{$s}%");
                        });
                });
            })
            // STATUS filter
            ->when($this->statusFilter !== '', fn (Builder $q) =>
                $q->where('status', $this->statusFilter)
            )
            // USER TYPE filter
            ->when($this->userType === 'student', fn (Builder $q) =>
                $q->whereHas('user', fn ($u) => $u->whereNotNull('student_id'))
            )
            ->when($this->userType === 'employee', fn (Builder $q) =>
                $q->whereHas('user', fn ($u) => $u->whereNotNull('employee_id'))
            )
            // DATE RANGE filter
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

    // Optional: keep your manual Refresh button working if you still show it
    public function refreshLogs()
    {
        $this->resetPage();
    }
}
