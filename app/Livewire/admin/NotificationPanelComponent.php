<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\ActivityLog;

class NotificationPanelComponent extends Component
{
    public $logs = [];

    public function mount()
    {
        // Fetch 8 latest logs where action = 'denied_entry'
        $this->logs = ActivityLog::where('action', 'denied_entry')
            ->latest()
            ->take(6)
            ->get(['details', 'created_at']);
    }

    public function render()
    {
        return view('livewire.admin.notification-panel-component');
    }
}
