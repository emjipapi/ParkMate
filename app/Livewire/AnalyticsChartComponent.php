<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsChartComponent extends Component
{
    public $labels = [];
    public $data = [];
    public $selectedDate;
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
        \Log::info('Selected date changed to: ' . $this->selectedDate); // Debug log
        $this->loadData();
        
        // Use only the custom event method that's working
        $eventData = [
            'labels' => $this->labels,
            'data' => $this->data
        ];
        
        // JavaScript custom event - this one is working
        $this->js("
            document.dispatchEvent(new CustomEvent('chartDataUpdated', { 
                detail: " . json_encode($eventData) . " 
            }));
        ");
    }

    public function loadData()
    {
        $entries = DB::table('activity_logs')
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as total')
            ->where('action', 'entry')
            ->whereDate('created_at', $this->selectedDate)
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        $this->labels = $entries->pluck('hour')->map(fn($h) => sprintf('%02d:00', $h))->toArray();
        $this->data = $entries->pluck('total')->toArray();
    }

    public function render()
    {
        return view('livewire.analytics-chart-component');
    }
}