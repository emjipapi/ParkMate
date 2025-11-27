<?php
namespace App\Livewire\Admin;

use App\Models\ActivityLog;
use Livewire\Component;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class LiveAttendanceComponent extends Component
{
    public $scans = [];

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
        ->whereNull('area_id')
        ->where(function ($q) {
            // Regular entry/exit
            $q->where(function ($sub) {
                $sub->where('actor_type', 'user')
                    ->whereIn('action', ['entry', 'exit'])
                    ->whereNotNull('details')
                    ->where('details', 'like', '%main gate%');
            })
            // Denied entries - more flexible conditions
            ->orWhere(function ($sub2) {
                $sub2->where('action', 'denied_entry')
                     ->whereNotNull('details');
                // Removed actor_type requirement - might be NULL or different
            });
        })
        ->orderBy('created_at', 'desc')
        ->take(3)
        ->get()
        ->map(function ($log) {
            $user = $log->user;

            $status = $log->action === 'denied_entry' ? 'DENIED' : ($log->action === 'entry' ? 'IN' : 'OUT');
                        // Determine user type
            $userType = 'Unknown';
            if ($user) {
                if (!empty($user->student_id)) {
                    $userType = 'Student';
                } elseif (!empty($user->employee_id)) {
                    $userType = 'Employee';
                } else {
                    $userType = 'Guest';
                }
            }

            // Extract vehicle type from details if available
            $vehicleType = null;
            if ($log->details) {
                if (stripos($log->details, 'motorcycle') !== false) {
                    $vehicleType = 'Motorcycle';
                } elseif (stripos($log->details, 'car') !== false) {
                    $vehicleType = 'Car';
                }
            }

            return [
                'name'    => $user ? "{$user->lastname}, {$user->firstname}" : 'Unknown',
                'status'  => $status,
                'picture' => $user && $user->profile_picture
                    ? route('profile.picture', ['filename' => $user->profile_picture])
                    : asset('images/placeholder.jpg'),
                'time'    => optional($log->created_at)->toDateTimeString(),
                                'user_type' => $userType,
                'vehicle_type' => $vehicleType,
            ];
        })
        ->toArray();
}


    public function render()
    {
        return view('livewire.admin.live-attendance-component');
    }
}