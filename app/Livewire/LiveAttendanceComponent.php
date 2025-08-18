<?php
namespace App\Livewire;
use App\Models\ActivityLog;

use Livewire\Component;

class LiveAttendanceComponent extends Component
{
    // 🕒 Cooldown duration in seconds
    private $cooldownSeconds = 5;
    public $profilePicture;

    public $namedEpcs = [];
    public $latestEpc = null; // 👈 add this here
    public $status = null;
    // private $epcNames = [
    //     '3268191180' => 'MJ',
    //     '3268191184' => 'Jobert',
    // ];

    private $cooldowns = [];
    private $lastStates = [];


    public $scans = [];

    public function mount()
    {
        // Initialize with placeholder
        $this->profilePicture = asset('images/placeholder.jpg');
    }

    public function pollEpc()
    {
        $scannedTags = \Illuminate\Support\Facades\Cache::pull('epc_list', []);
        $now = now();

        $cooldowns = \Cache::get('rfid_cooldowns', []);
        $lastStates = \Cache::get('rfid_last_states', []);

        foreach ($scannedTags as $epc) {
            // 🟢 Get user from DB
            $user = \App\Models\User::where('rfid_tag', $epc)->first();
            if (!$user) {
                $this->namedEpcs[] = "$epc - Unknown";
                continue;
            }

            $name = "{$user->lastname}, {$user->firstname}";

            // 🟡 Check cooldown
            if (isset($cooldowns[$epc]) && $now->lt($cooldowns[$epc])) {
                continue;
            }

            // 🔁 Toggle in_out
            $isCurrentlyIn = $user->in_out === 'IN';
            $newStatus = $isCurrentlyIn ? 'OUT' : 'IN';
            $user->in_out = $newStatus;
            $user->save();

            // 📝 Log name and status
            $this->namedEpcs[] = "$name ($epc) - $newStatus";
            $this->latestEpc = "$name ($epc)";
            $this->status = $newStatus;
            // Set profile picture, or placeholder if not uploaded
            $this->profilePicture = $user->profile_picture
                ? route('profile.picture', ['filename' => $user->profile_picture])
                : asset('images/placeholder.jpg');



            // 🔄 Update lastStates and cooldowns
            $lastStates[$epc] = !$isCurrentlyIn;
            $cooldowns[$epc] = $now->addSeconds($this->cooldownSeconds);
            // 📝 Log scan
            $scan = [
                'name' => "$name ($epc)",
                'status' => $newStatus,
                'picture' => $user->profile_picture
                    ? route('profile.picture', ['filename' => $user->profile_picture])
                    : asset('images/placeholder.jpg'),
            ];

            // Put newest scan at the start
            array_unshift($this->scans, $scan);

            // Keep only the last 10 for frontend queue
            $this->scans = array_slice($this->scans, 0, 3);

                    // Log activity
        ActivityLog::create([
            'user_id' => $user->id,
            'rfid_tag' => $epc,
            'status' => $newStatus,
        ]);
        }

        \Cache::put('rfid_cooldowns', $cooldowns, 60);
        \Cache::put('rfid_last_states', $lastStates, 60);
    }
    private function getScannedTags()
    {
        // 🟡 Replace this with your real reader logic
        return session('scanned_epcs', []); // Example only
    }
}