<?php
namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\ActivityLog;

class ActivityLogComponent extends Component
{
    use WithPagination;

    public $search = '';

    public function updatingSearch()
    {
        // reset to page 1 when typing a new search
        $this->resetPage();
    }

    public function render()
    {
        $activityLogs = ActivityLog::with('user')
            ->when($this->search, function ($query) {
                $s = $this->search;

                $query->where(function ($q) use ($s) {
                    // Search RFID directly on activity_logs
                    $q->where('rfid_tag', 'like', "%{$s}%")
                      ->orWhereHas('user', function ($uq) use ($s) {
                          // Search user fields
                          $uq->where('firstname', 'like', "%{$s}%")
                             ->orWhere('lastname', 'like', "%{$s}%")
                             ->orWhereRaw("CONCAT_WS(' ', firstname, middlename, lastname) LIKE ?", ["%$s%"])
                             ->orWhere('student_id', 'like', "%{$s}%")
                             ->orWhere('employee_id', 'like', "%{$s}%");
                      });
                });
            })
            ->latest()
            ->paginate(10);

        return view('livewire.activity-log-component', compact('activityLogs'));
    }
    public function refreshLogs()
{
    // Simply re-fetch data, Livewire will auto re-render
    $this->resetPage(); 
}
}
