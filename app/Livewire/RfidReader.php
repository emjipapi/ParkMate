<?php

namespace App\Livewire;

use Livewire\Component;

class RfidReader extends Component
{
    public $namedEpcs = [];
    public $latestEpc = null; // 👈 add this here

    // private $epcNames = [
    //     '3268191180' => 'MJ',
    //     '3268191184' => 'Jobert',
    // ];

    private $cooldowns = [];
    private $lastStates = [];

    // 🕒 Cooldown duration in seconds
    private $cooldownSeconds = 5;

    
    
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
        $this->latestEpc = "$name ($epc) - $newStatus";



        // 🔄 Update lastStates and cooldowns
        $lastStates[$epc] = !$isCurrentlyIn;
        $cooldowns[$epc] = $now->addSeconds($this->cooldownSeconds);
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