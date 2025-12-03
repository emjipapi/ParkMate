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
    
    // Daily summary statistics
    public $peakHour = null;
    public $totalEntries = 0;
    public $quietestHour = null;
    public $averagePerHour = 0;
    public $busyPeriod = null;

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
            
            // Calculate daily summary statistics
            $this->calculateDailySummaryStats();
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
        elseif ($this->period === 'monthly') {
            // Daily breakdown for all days in selected month
            $date = Carbon::parse($this->selectedDate);
            $startDate = $date->copy()->startOfMonth();
            $endDate = $date->copy()->endOfMonth();
            $daysInMonth = $date->daysInMonth;

            $entries = DB::table('activity_logs')
                ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
                ->where('action', 'entry')
                ->where('actor_type', 'user')
                ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->keyBy('date');

            // Generate labels and data for all days in month (fill missing days with 0)
            $this->labels = [];
            $this->data = [];
            for ($i = 1; $i <= $daysInMonth; $i++) {
                $date = $startDate->copy()->addDays($i - 1);
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

    private function calculateDailySummaryStats()
    {
        // Total entries
        $this->totalEntries = array_sum($this->data);
        
        // Peak hour and quietest hour
        if (!empty($this->data)) {
            $peakIndex = 0;
            $quietIndex = 0;
            $maxValue = $this->data[0];
            $minValue = $this->data[0];
            
            foreach ($this->data as $index => $value) {
                if ($value > $maxValue) {
                    $maxValue = $value;
                    $peakIndex = $index;
                }
                if ($value < $minValue) {
                    $minValue = $value;
                    $quietIndex = $index;
                }
            }
            
            $this->peakHour = [
                'hour' => $peakIndex,
                'count' => $this->data[$peakIndex],
                'formatted' => sprintf('%02d:00', $peakIndex)
            ];
            
            $this->quietestHour = [
                'hour' => $quietIndex,
                'count' => $this->data[$quietIndex],
                'formatted' => sprintf('%02d:00', $quietIndex)
            ];
        }
        
        // Average per hour
        $this->averagePerHour = $this->totalEntries > 0 ? round($this->totalEntries / 24, 1) : 0;
        
        // Busiest period (6-hour windows: Night, Morning, Afternoon, Evening)
        $periods = [
            ['name' => 'Night', 'start' => 0, 'end' => 6],
            ['name' => 'Morning', 'start' => 6, 'end' => 12],
            ['name' => 'Afternoon', 'start' => 12, 'end' => 18],
            ['name' => 'Evening', 'start' => 18, 'end' => 24],
        ];
        
        $maxPeriodCount = 0;
        $this->busyPeriod = null;
        
        foreach ($periods as $period) {
            $count = 0;
            for ($i = $period['start']; $i < $period['end']; $i++) {
                $count += $this->data[$i] ?? 0;
            }
            
            if ($count > $maxPeriodCount) {
                $maxPeriodCount = $count;
                $this->busyPeriod = [
                    'name' => $period['name'],
                    'start' => sprintf('%02d:00', $period['start']),
                    'end' => sprintf('%02d:00', $period['end']),
                    'count' => $count
                ];
            }
        }
        
        // Clear stats for non-daily periods
        if ($this->period !== 'daily') {
            $this->peakHour = null;
            $this->quietestHour = null;
            $this->busyPeriod = null;
            $this->totalEntries = 0;
            $this->averagePerHour = 0;
        }
    }

    public function render()
    {
        return view('livewire.admin.analytics-chart-component');
    }
}