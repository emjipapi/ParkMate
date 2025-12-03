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
    public $period = 'daily'; // For entry analytics: daily, weekly, monthly

    public function mount()
    {
        // Get all unique dates from the activity_logs table
        $this->dates = DB::table('activity_logs')
            ->selectRaw('DATE(created_at) as date')
            ->where('actor_type', 'user') // Only get dates where users had activity
            ->distinct()
            ->orderBy('date', 'desc') // Most recent first
            ->pluck('date')
            ->toArray();

        // Set default selected date to the most recent date if available
        $this->selectedDate = !empty($this->dates) ? $this->dates[0] : Carbon::today()->toDateString();
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

    public function updatedPeriod()
    {
        \Log::info('Period changed to: ' . $this->period);
        $this->loadData();
        $this->emitChartUpdate();
    }

    public function loadData()
    {
        if ($this->chartType === 'entries') {
            $this->loadEntriesData();
        } elseif ($this->chartType === 'duration') {
            $this->loadDurationData();
        } elseif ($this->chartType === 'logins') {
            $this->loadLoginsData('user');
        } elseif ($this->chartType === 'admin_logins') {
            $this->loadLoginsData('admin');
        }
    }

    private function loadEntriesData()
    {
        if ($this->period === 'daily') {
            // Hourly breakdown for selected date
            $entries = DB::table('activity_logs')
                ->selectRaw('HOUR(created_at) as hour, COUNT(*) as total')
                ->where('action', 'entry')
                ->where('actor_type', 'user')
                ->whereDate('created_at', $this->selectedDate)
                ->groupBy('hour')
                ->orderBy('hour')
                ->get()
                ->keyBy('hour');

            // Generate labels and data for all 24 hours (fill missing hours with 0)
            $this->labels = [];
            $this->data = [];
            for ($hour = 0; $hour < 24; $hour++) {
                $this->labels[] = sprintf('%02d:00', $hour);
                $this->data[] = $entries->get($hour)?->total ?? 0;
            }
        } 
        elseif ($this->period === 'weekly') {
            // Daily breakdown for 7 days starting from selected date
            $startDate = Carbon::parse($this->selectedDate);
            $endDate = $startDate->copy()->addDays(6);

            $entries = DB::table('activity_logs')
                ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
                ->where('action', 'entry')
                ->where('actor_type', 'user')
                ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->keyBy('date');

            // Generate labels and data for all 7 days (fill missing days with 0)
            $this->labels = [];
            $this->data = [];
            for ($i = 0; $i < 7; $i++) {
                $date = $startDate->copy()->addDays($i);
                $dateStr = $date->format('Y-m-d');
                $this->labels[] = $date->format('M d');
                $this->data[] = $entries->get($dateStr)?->total ?? 0;
            }
        }
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

    private function loadLoginsData($actorType)
    {
        $logins = DB::table('activity_logs')
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as total')
            ->where('action', 'login')
            ->where('actor_type', $actorType) // can be 'user' or 'admin'
            ->whereDate('created_at', $this->selectedDate)
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        $this->labels = $logins->pluck('hour')->map(fn($h) => sprintf('%02d:00', $h))->toArray();
        $this->data = $logins->pluck('total')->toArray();
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