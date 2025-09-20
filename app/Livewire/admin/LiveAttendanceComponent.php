<?php
namespace App\Livewire\Admin;

use App\Models\ActivityLog;
use Livewire\Component;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class LiveAttendanceComponent extends Component
{
    public $scans = [];
    private $cooldownSeconds = 5;

    public function mount()
    {
        $this->loadLatestScans();
    }

    public function pollEpc()
    {
        // Update frontend
        $this->loadLatestScans();
    }

   public function loadLatestScans()
    {
        $this->scans = ActivityLog::with('user')
            ->where(function($query) {
                $query->where('actor_type', 'user')                           // regular user scans
                      ->whereIn('action', ['entry', 'exit'])
                      ->orWhere(function($subQuery) {
                          $subQuery->where('actor_type', 'system')             // system denied entries
                                   ->where('action', 'denied_entry');
                      });
            })
            ->latest()
            ->take(3)
            ->get()
            ->map(function ($log) {
                // Now we can use the user relationship for all types since actor_id contains the user ID
                $user = $log->user;

                // Determine status based on action
                if ($log->action === 'denied_entry') {
                    $status = 'DENIED';
                } elseif ($log->action === 'entry') {
                    $status = 'IN';
                } else {
                    $status = 'OUT';
                }

                return [
                    'name' => "{$user->lastname}, {$user->firstname}",
                    'status' => $status,
                    'picture' => $user->profile_picture
                        ? route('profile.picture', ['filename' => $user->profile_picture])
                        : asset('images/placeholder.jpg'),
                ];
            })
            ->toArray();
    }

    public function render()
    {
        return view('livewire.admin.live-attendance-component');
    }
}