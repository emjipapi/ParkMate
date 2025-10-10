<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\UnknownRfidLog;
use Carbon\Carbon;

class UnknownEpcsComponent extends Component
{
    // how long an unknown tag should remain visible (seconds)
    public int $displaySeconds = 30;

    // max number of stacked items to show
    public int $maxItems = 6;

    public function render()
    {
        $since = now()->subSeconds($this->displaySeconds);

        // get recent unknown logs within the display window, newest first
        $unknowns = UnknownRfidLog::where('created_at', '>=', $since)
            ->orderBy('created_at', 'desc')
            ->take($this->maxItems)
            ->get();

        return view('livewire.admin.unknown-epcs-component', [
            'unknowns' => $unknowns,
            'displaySeconds' => $this->displaySeconds,
        ]);
    }
}
