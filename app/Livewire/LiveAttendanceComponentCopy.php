<?php
namespace App\Livewire;

use App\Models\ActivityLog;
use Livewire\Component;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class LiveAttendanceComponentCopy extends Component
{
    public $scans = [];
    private $cooldownSeconds = 5;

    public function mount()
    {
        $this->loadLatestScans();
    }

    public function pollEpc()
    {
        $scannedTags = Cache::pull('epc_list', []);
        $now = now();

        foreach ($scannedTags as $epc) {
            $user = \App\Models\User::where('rfid_tag', $epc)->first();
            if (!$user) continue;

            // Get last scan for this EPC
            $lastScan = ActivityLog::where('rfid_tag', $epc)->latest()->first();

            // Skip if within cooldown
            if ($lastScan && $lastScan->created_at->diffInSeconds($now) < $this->cooldownSeconds) {
                continue;
            }

            // Determine new status based on last scan
            $newStatus = $lastScan && $lastScan->status === 'IN' ? 'OUT' : 'IN';

            // Create new activity log
            ActivityLog::create([
                'user_id' => $user->id,
                'rfid_tag' => $epc,
                'status' => $newStatus,
                'details' => "User {$user->firstname} {$user->lastname} scanned {$newStatus}",
            ]);
        }

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
        return view('livewire.live-attendance-component-copy');
    }
}