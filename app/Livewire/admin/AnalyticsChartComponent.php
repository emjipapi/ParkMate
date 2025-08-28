<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsChartComponent extends Component
{
    public $labels = [];
    public $data = [];
    public $selectedDate;
    public $chartType = 'entries'; // Default to entries
    public $dates = [];

    public function mount()
    {
        $this->dates = collect(range(0,6))
            ->map(fn($i) => Carbon::today()->subDays($i)->toDateString())
            ->toArray();

        $this->selectedDate = $this->dates[0];
        $this->loadData();
    }

    public function updatedSelectedDate()
    {
        \Log::info('Selected date changed to: ' . $this->selectedDate);
        $this->loadData();
        $this->emitChartUpdate();
    }

    public function updatedChartType()
    {
        \Log::info('Chart type changed to: ' . $this->chartType);
        $this->loadData();
        $this->emitChartUpdate();
    }

    public function loadData()
    {
        if ($this->chartType === 'entries') {
            $this->loadEntriesData();
        } else {
            $this->loadDurationData();
        }
    }

    private function loadEntriesData()
    {
        $entries = DB::table('activity_logs')
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as total')
            ->where('action', 'entry')
            ->where('actor_type', 'user') // Only count user entries, not admin logins
            ->whereDate('created_at', $this->selectedDate)
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        $this->labels = $entries->pluck('hour')->map(fn($h) => sprintf('%02d:00', $h))->toArray();
        $this->data = $entries->pluck('total')->toArray();
    }

    private function loadDurationData()
    {
        // Calculate average duration of stays by matching entry/exit pairs for users
        $durations = DB::select("
            SELECT 
                HOUR(entries.created_at) as hour,
                AVG(TIMESTAMPDIFF(MINUTE, entries.created_at, exits.created_at)) as avg_duration
            FROM activity_logs entries
            LEFT JOIN activity_logs exits ON 
                entries.actor_type = exits.actor_type 
                AND entries.actor_id = exits.actor_id
                AND exits.action = 'exit'
                AND exits.created_at > entries.created_at
                AND DATE(exits.created_at) = DATE(entries.created_at)
            WHERE 
                entries.action = 'entry'
                AND entries.actor_type = 'user'
                AND DATE(entries.created_at) = ?
                AND exits.id IS NOT NULL
            GROUP BY HOUR(entries.created_at)
            ORDER BY hour
        ", [$this->selectedDate]);

        $this->labels = collect($durations)->pluck('hour')->map(fn($h) => sprintf('%02d:00', $h))->toArray();
        $this->data = collect($durations)->pluck('avg_duration')->map(fn($d) => round($d, 1))->toArray();
    }

    private function emitChartUpdate()
    {
        $eventData = [
            'labels' => $this->labels,
            'data' => $this->data,
            'chartType' => $this->chartType
        ];
        
        $this->js("
            document.dispatchEvent(new CustomEvent('chartDataUpdated', { 
                detail: " . json_encode($eventData) . " 
            }));
        ");
    }

    public function render()
    {
        return view('livewire.admin.analytics-chart-component');
    }
}