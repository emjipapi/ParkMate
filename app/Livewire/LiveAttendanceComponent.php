<?php
namespace App\Livewire;

use App\Models\ActivityLog;
use Livewire\Component;

class LiveAttendanceComponent extends Component
{
    public $profilePicture;
    public $latestEpc = null;
    public $status = null;
    public $scans = [];

    // Cooldown in seconds to prevent duplicate logs
    private $cooldownSeconds = 5;

    public function mount()
    {
        $this->profilePicture = asset('images/placeholder.jpg');
        $this->loadLatestScans();
    }

    public function pollEpc()
    {
        $scannedTags = \Illuminate\Support\Facades\Cache::pull('epc_list', []);
        $now = now();

        foreach ($scannedTags as $epc) {
            $user = \App\Models\User::where('rfid_tag', $epc)->first();
            if (!$user) continue;

            // Check last scan for cooldown
            $lastScan = ActivityLog::where('rfid_tag', $epc)
                ->latest()
                ->first();

            if ($lastScan && $lastScan->created_at->diffInSeconds($now) < $this->cooldownSeconds) {
                continue; // skip if last scan was too recent
            }

            // Toggle status
            $newStatus = $user->in_out === 'IN' ? 'OUT' : 'IN';
            $user->in_out = $newStatus;
            $user->save();

            // Log activity
            ActivityLog::create([
                'user_id' => $user->id,
                'rfid_tag' => $epc,
                'status' => $newStatus,
            ]);
        }

        // Reload latest scans for the frontend
        $this->loadLatestScans();
    }

    public function loadLatestScans()
    {
        $this->scans = ActivityLog::latest()
            ->take(3)
            ->get()
            ->map(function ($log) {
                return [
                    'name' => "{$log->user->lastname}, {$log->user->firstname}",
                    'status' => $log->status,
                    'picture' => $log->user->profile_picture
                        ? route('profile.picture', ['filename' => $log->user->profile_picture])
                        : asset('images/placeholder.jpg'),
                ];
            })
            ->toArray();

        if (!empty($this->scans)) {
            $this->latestEpc = $this->scans[0]['name'];
            $this->status = $this->scans[0]['status'];
            $this->profilePicture = $this->scans[0]['picture'];
        }
    }

    public function render()
    {
        return view('livewire.live-attendance-component');
    }
}
