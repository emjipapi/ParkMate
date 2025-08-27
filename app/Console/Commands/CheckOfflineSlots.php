<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CarSlot;

class CheckOfflineSlots extends Command
{
    protected $signature = 'slots:check-offline';
    protected $description = 'Reset car slots that have been offline too long';

    public function handle()
    {
        $offlineSlots = CarSlot::where('last_seen', '<', now()->subSeconds(15))
                              ->where('occupied', 1)
                              ->get();

        foreach ($offlineSlots as $slot) {
            $slot->update(['occupied' => 0]);
            $this->info("Reset slot {$slot->label} in area {$slot->area_id}");
        }

        $this->info("Checked offline slots: " . $offlineSlots->count() . " reset");
        return 0;
    }
}