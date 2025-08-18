<?php
namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\ActivityLog;

class ActivityLogComponent extends Component
{
    use WithPagination;

    public $search;

    public function render()
    {
        $activityLogs = ActivityLog::with('user') // ✅ model query
            ->when($this->search, fn($query) => 
                $query->whereHas('user', fn($q) => 
                    $q->where('firstname', 'like', "%{$this->search}%")
                      ->orWhere('lastname', 'like', "%{$this->search}%")
                      ->orWhere('rfid_tag', 'like', "%{$this->search}%")
                )
            )
            ->latest()
            ->paginate(10);

        return view('livewire.activity-log-component', compact('activityLogs'));
    }
}