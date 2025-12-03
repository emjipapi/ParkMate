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
    
    // Weekly summary statistics
    public $peakDay = null;
    public $quietestDay = null;
    public $averagePerDay = 0;
    public $busiestDay = null;
    
    // Monthly summary statistics
    public $peakDateMonth = null;
    public $quietestDateMonth = null;
    public $averagePerDateMonth = 0;
    public $busiestDateMonth = null;
    
    public $vehicleTypeBreakdown = [];
    public $userTypeBreakdown = [];
    public $userTypeVehicleBreakdown = [];
    public $parkingAreaBreakdown = [];
    public $timeRanges = [];

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
            
            // Calculate weekly summary statistics
            $this->calculateWeeklySummaryStats($startDate, $endDate);
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
            
            // Calculate monthly summary statistics
            $this->calculateMonthlySummaryStats($startDate, $endDate);
        }
    }

    private function loadDurationData()
    {
        // Calculate average duration of stays by matching entry/exit pairs for users at Main Gate only
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
                AND exits.area_id IS NULL
            WHERE 
                entries.action = 'entry'
                AND entries.actor_type = 'user'
                AND DATE(entries.created_at) = ?
                AND entries.area_id IS NULL
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
            $this->averagePerHour = 0;
            $this->vehicleTypeBreakdown = [];
            $this->userTypeBreakdown = [];
            $this->userTypeVehicleBreakdown = [];
            $this->parkingAreaBreakdown = [];
            $this->timeRanges = [];
        }
        
        // Clear weekly stats if not in weekly mode
        if ($this->period !== 'weekly') {
            $this->peakDay = null;
            $this->quietestDay = null;
            $this->busiestDay = null;
            $this->averagePerDay = 0;
        }
        
        // Clear monthly stats if not in monthly mode
        if ($this->period !== 'monthly') {
            $this->peakDateMonth = null;
            $this->quietestDateMonth = null;
            $this->busiestDateMonth = null;
            $this->averagePerDateMonth = 0;
        }
        
        // Only calculate breakdowns for daily view
        if ($this->period === 'daily') {
            $this->calculateVehicleTypeBreakdown();
            $this->calculateUserTypeBreakdown();
            $this->calculateParkingAreaBreakdown();
            $this->calculateTimeRanges();
        }
    }

    private function calculateTimeRanges()
    {
        // Calculate entries by time range
        $this->timeRanges = [
            ['name' => 'Morning', 'label' => 'Morning (06:00-12:00)', 'start' => 6, 'end' => 12, 'count' => 0],
            ['name' => 'Afternoon', 'label' => 'Afternoon (12:00-18:00)', 'start' => 12, 'end' => 18, 'count' => 0],
            ['name' => 'Evening', 'label' => 'Evening (18:00-00:00)', 'start' => 18, 'end' => 24, 'count' => 0],
            ['name' => 'Night', 'label' => 'Night (00:00-06:00)', 'start' => 0, 'end' => 6, 'count' => 0],
        ];

        foreach ($this->timeRanges as &$range) {
            for ($i = $range['start']; $i < $range['end']; $i++) {
                $range['count'] += $this->data[$i] ?? 0;
            }
        }
    }

    private function calculateWeeklySummaryStats($startDate, $endDate)
    {
        // Total entries for the week
        $this->totalEntries = array_sum($this->data);

        // Find peak and quietest days
        if (count($this->data) > 0) {
            $maxValue = PHP_INT_MIN;
            $minValue = PHP_INT_MAX;
            $peakIndex = 0;
            $quietIndex = 0;

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

            $peakDate = $startDate->copy()->addDays($peakIndex);
            $quietDate = $startDate->copy()->addDays($quietIndex);

            $this->peakDay = [
                'day' => $peakDate->format('l'),
                'date' => $peakDate->format('M d'),
                'count' => $this->data[$peakIndex]
            ];

            $this->quietestDay = [
                'day' => $quietDate->format('l'),
                'date' => $quietDate->format('M d'),
                'count' => $this->data[$quietIndex]
            ];

            // Busiest day (same as peak day)
            $this->busiestDay = [
                'day' => $peakDate->format('l'),
                'date' => $peakDate->format('M d'),
                'count' => $this->data[$peakIndex]
            ];
        }

        // Average per day
        $this->averagePerDay = $this->totalEntries > 0 ? round($this->totalEntries / 7, 1) : 0;
        
        // Calculate weekly breakdowns
        $this->calculateWeeklyBreakdowns($startDate, $endDate);
    }

    private function calculateMonthlySummaryStats($startDate, $endDate)
    {
        // Total entries for the month
        $this->totalEntries = array_sum($this->data);

        // Find peak and quietest dates
        if (count($this->data) > 0) {
            $maxValue = PHP_INT_MIN;
            $minValue = PHP_INT_MAX;
            $peakIndex = 0;
            $quietIndex = 0;

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

            $peakDate = $startDate->copy()->addDays($peakIndex);
            $quietDate = $startDate->copy()->addDays($quietIndex);

            $this->peakDateMonth = [
                'day' => $peakDate->format('l'),
                'date' => $peakDate->format('M d'),
                'count' => $this->data[$peakIndex]
            ];

            $this->quietestDateMonth = [
                'day' => $quietDate->format('l'),
                'date' => $quietDate->format('M d'),
                'count' => $this->data[$quietIndex]
            ];

            // Busiest date (same as peak date)
            $this->busiestDateMonth = [
                'day' => $peakDate->format('l'),
                'date' => $peakDate->format('M d'),
                'count' => $this->data[$peakIndex]
            ];
        }

        // Average per date
        $daysInMonth = $endDate->day;
        $this->averagePerDateMonth = $this->totalEntries > 0 ? round($this->totalEntries / $daysInMonth, 1) : 0;
        
        // Calculate monthly breakdowns
        $this->calculateMonthlyBreakdowns($startDate, $endDate);
    }

    private function calculateVehicleTypeBreakdown()
    {
        // Get all vehicle types in the system
        $allVehicleTypes = DB::table('vehicles')
            ->distinct()
            ->pluck('type')
            ->toArray();

        // Get all users who had entries on this date
        $userIds = DB::table('activity_logs')
            ->where('action', 'entry')
            ->where('actor_type', 'user')
            ->whereDate('created_at', $this->selectedDate)
            ->distinct()
            ->pluck('actor_id');

        // Count vehicles by type for these users
        $vehicleTypeCounts = [];
        if ($userIds->count() > 0) {
            $vehicleTypeCounts = DB::table('vehicles')
                ->whereIn('user_id', $userIds)
                ->selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->get()
                ->keyBy('type')
                ->toArray();
        }

        // Include all vehicle types with their counts (0 if not found)
        $this->vehicleTypeBreakdown = [];
        foreach ($allVehicleTypes as $vehicleType) {
            $this->vehicleTypeBreakdown[] = (object)[
                'type' => $vehicleType,
                'count' => isset($vehicleTypeCounts[$vehicleType]) ? $vehicleTypeCounts[$vehicleType]->count : 0
            ];
        }
    }

    private function calculateUserTypeBreakdown()
    {
        // Get total entries by user type on this date
        // User type is determined by: if student_id is filled = student, if employee_id = employee, if both null = guest
        $userTypeCounts = DB::table('activity_logs')
            ->join('users', 'activity_logs.actor_id', '=', 'users.id')
            ->where('activity_logs.action', 'entry')
            ->where('activity_logs.actor_type', 'user')
            ->whereDate('activity_logs.created_at', $this->selectedDate)
            ->selectRaw("
                CASE 
                    WHEN users.student_id IS NOT NULL THEN 'student'
                    WHEN users.employee_id IS NOT NULL THEN 'employee'
                    ELSE 'guest'
                END as user_type,
                COUNT(*) as count
            ")
            ->groupByRaw("
                CASE 
                    WHEN users.student_id IS NOT NULL THEN 'student'
                    WHEN users.employee_id IS NOT NULL THEN 'employee'
                    ELSE 'guest'
                END
            ")
            ->get()
            ->keyBy('user_type')
            ->toArray();

        // Always include all user types with counts (0 if not found)
        $userTypes = ['student', 'employee', 'guest'];
        $this->userTypeBreakdown = [];
        foreach ($userTypes as $userType) {
            $this->userTypeBreakdown[] = (object)[
                'user_type' => $userType,
                'count' => isset($userTypeCounts[$userType]) ? $userTypeCounts[$userType]->count : 0
            ];
        }

        // Get vehicle type breakdown by user type
        $this->userTypeVehicleBreakdown = [];
        
        // First, get all vehicle types used in the system
        $allVehicleTypes = DB::table('vehicles')
            ->distinct()
            ->pluck('type')
            ->toArray();
        
        $userTypeMap = [
            'student' => 'users.student_id IS NOT NULL',
            'employee' => 'users.employee_id IS NOT NULL AND users.student_id IS NULL',
            'guest' => 'users.student_id IS NULL AND users.employee_id IS NULL'
        ];

        foreach ($userTypeMap as $userType => $condition) {
            $userIds = DB::table('activity_logs')
                ->join('users', 'activity_logs.actor_id', '=', 'users.id')
                ->where('activity_logs.action', 'entry')
                ->where('activity_logs.actor_type', 'user')
                ->whereDate('activity_logs.created_at', $this->selectedDate)
                ->whereRaw($condition)
                ->distinct()
                ->pluck('activity_logs.actor_id');

            $vehicleBreakdown = [];
            if ($userIds->count() > 0) {
                $vehicleBreakdown = DB::table('vehicles')
                    ->whereIn('user_id', $userIds)
                    ->selectRaw('type, COUNT(*) as count')
                    ->groupBy('type')
                    ->get()
                    ->keyBy('type')
                    ->toArray();
            }

            // Include all vehicle types, with 0 for missing ones
            $completeBreakdown = [];
            foreach ($allVehicleTypes as $vehicleType) {
                $completeBreakdown[] = (object)[
                    'type' => $vehicleType,
                    'count' => isset($vehicleBreakdown[$vehicleType]) ? $vehicleBreakdown[$vehicleType]->count : 0
                ];
            }

            $this->userTypeVehicleBreakdown[$userType] = $completeBreakdown;
        }
    }

    private function calculateParkingAreaBreakdown()
    {
        // Get all parking areas from database (excluding soft deleted)
        $allAreas = DB::table('parking_areas')->whereNull('deleted_at')->select('id', 'name')->get();

        // Get entry counts by area for this date (only from active areas)
        $activeAreaIds = $allAreas->pluck('id')->toArray();
        $areaEntryCounts = DB::table('activity_logs')
            ->where('action', 'entry')
            ->where('actor_type', 'user')
            ->whereDate('created_at', $this->selectedDate)
            ->whereIn('area_id', $activeAreaIds)
            ->selectRaw('area_id, COUNT(*) as count')
            ->groupBy('area_id')
            ->get()
            ->keyBy('area_id')
            ->toArray();

        // Build complete breakdown with all active areas
        $this->parkingAreaBreakdown = [];

        // Add Main Gate (entries with NULL area_id)
        $mainGateCount = DB::table('activity_logs')
            ->where('action', 'entry')
            ->where('actor_type', 'user')
            ->whereDate('created_at', $this->selectedDate)
            ->whereNull('area_id')
            ->count();

        $this->parkingAreaBreakdown[] = (object)[
            'name' => 'Main Gate',
            'count' => $mainGateCount
        ];

        // Add all active parking areas
        foreach ($allAreas as $area) {
            $this->parkingAreaBreakdown[] = (object)[
                'name' => $area->name,
                'count' => isset($areaEntryCounts[$area->id]) ? $areaEntryCounts[$area->id]->count : 0
            ];
        }
    }

    private function calculateWeeklyBreakdowns($startDate, $endDate)
    {
        // Get all data for the week
        $weekData = DB::table('activity_logs')
            ->where('action', 'entry')
            ->where('actor_type', 'user')
            ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->get();

        // Calculate vehicle type breakdown
        $this->calculateWeeklyVehicleTypeBreakdown($weekData);
        
        // Calculate user type breakdown
        $this->calculateWeeklyUserTypeBreakdown($startDate, $endDate);
        
        // Calculate user type vehicle breakdown
        $this->calculateWeeklyUserTypeVehicleBreakdown($startDate, $endDate);
        
        // Calculate parking area breakdown
        $this->calculateWeeklyParkingAreaBreakdown($startDate, $endDate);
        
        // Calculate time ranges
        $this->calculateWeeklyTimeRanges($weekData);
    }

    private function calculateWeeklyVehicleTypeBreakdown($weekData)
    {
        // Get all vehicle types from system
        $allVehicleTypes = DB::table('vehicles')
            ->distinct('type')
            ->pluck('type');

        // Count vehicles in entries
        $vehicleCounts = [];
        $totalVehicles = 0;

        foreach ($weekData as $entry) {
            $userVehicles = DB::table('vehicles')
                ->where('user_id', $entry->actor_id)
                ->pluck('type');

            foreach ($userVehicles as $type) {
                $vehicleCounts[$type] = ($vehicleCounts[$type] ?? 0) + 1;
                $totalVehicles++;
            }
        }

        // Build breakdown with all vehicle types
        $this->vehicleTypeBreakdown = [];
        foreach ($allVehicleTypes as $type) {
            $count = $vehicleCounts[$type] ?? 0;
            $percentage = $totalVehicles > 0 ? round(($count / $totalVehicles) * 100, 1) : 0;

            $this->vehicleTypeBreakdown[] = (object)[
                'type' => $type,
                'count' => $count,
                'percentage' => $percentage
            ];
        }
    }

    private function calculateWeeklyUserTypeBreakdown($startDate, $endDate)
    {
        $userTypeCounts = DB::table('activity_logs')
            ->selectRaw("
                CASE
                    WHEN users.student_id IS NOT NULL THEN 'Student'
                    WHEN users.employee_id IS NOT NULL THEN 'Employee'
                    ELSE 'Guest'
                END as user_type,
                COUNT(*) as count
            ")
            ->leftJoin('users', 'activity_logs.actor_id', '=', 'users.id')
            ->where('activity_logs.action', 'entry')
            ->where('activity_logs.actor_type', 'user')
            ->whereBetween('activity_logs.created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->groupBy('user_type')
            ->get()
            ->keyBy('user_type')
            ->toArray();

        $this->userTypeBreakdown = [];
        $userTypes = ['Student', 'Employee', 'Guest'];
        $totalUsers = array_sum(array_map(fn($item) => $item->count, $userTypeCounts));

        foreach ($userTypes as $type) {
            $count = isset($userTypeCounts[$type]) ? $userTypeCounts[$type]->count : 0;
            $percentage = $totalUsers > 0 ? round(($count / $totalUsers) * 100, 1) : 0;

            $this->userTypeBreakdown[] = (object)[
                'type' => $type,
                'count' => $count,
                'percentage' => $percentage
            ];
        }
    }

    private function calculateWeeklyParkingAreaBreakdown($startDate, $endDate)
    {
        // Get all active parking areas
        $allAreas = DB::table('parking_areas')->whereNull('deleted_at')->select('id', 'name')->get();

        // Get entry counts by area for this week (only from active areas)
        $activeAreaIds = $allAreas->pluck('id')->toArray();
        $areaEntryCounts = DB::table('activity_logs')
            ->where('action', 'entry')
            ->where('actor_type', 'user')
            ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->whereIn('area_id', $activeAreaIds)
            ->selectRaw('area_id, COUNT(*) as count')
            ->groupBy('area_id')
            ->get()
            ->keyBy('area_id')
            ->toArray();

        // Build complete breakdown with all active areas
        $this->parkingAreaBreakdown = [];

        // Add Main Gate (entries with NULL area_id)
        $mainGateCount = DB::table('activity_logs')
            ->where('action', 'entry')
            ->where('actor_type', 'user')
            ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->whereNull('area_id')
            ->count();

        $this->parkingAreaBreakdown[] = (object)[
            'name' => 'Main Gate',
            'count' => $mainGateCount
        ];

        // Add all active parking areas
        foreach ($allAreas as $area) {
            $this->parkingAreaBreakdown[] = (object)[
                'name' => $area->name,
                'count' => isset($areaEntryCounts[$area->id]) ? $areaEntryCounts[$area->id]->count : 0
            ];
        }
    }

    private function calculateWeeklyTimeRanges($weekData)
    {
        // Calculate entries by time range for the week
        $this->timeRanges = [
            ['name' => 'Morning', 'label' => 'Morning (06:00-12:00)', 'start' => 6, 'end' => 12, 'count' => 0],
            ['name' => 'Afternoon', 'label' => 'Afternoon (12:00-18:00)', 'start' => 12, 'end' => 18, 'count' => 0],
            ['name' => 'Evening', 'label' => 'Evening (18:00-00:00)', 'start' => 18, 'end' => 24, 'count' => 0],
            ['name' => 'Night', 'label' => 'Night (00:00-06:00)', 'start' => 0, 'end' => 6, 'count' => 0],
        ];

        foreach ($weekData as $entry) {
            $hour = Carbon::parse($entry->created_at)->hour;

            foreach ($this->timeRanges as &$range) {
                if ($hour >= $range['start'] && $hour < $range['end']) {
                    $range['count']++;
                    break;
                }
            }
        }
    }

    private function calculateWeeklyUserTypeVehicleBreakdown($startDate, $endDate)
    {
        // Get all vehicle types from system
        $allVehicleTypes = DB::table('vehicles')
            ->distinct('type')
            ->pluck('type')
            ->toArray();

        // Get user types with their vehicles for the week
        $this->userTypeVehicleBreakdown = [];
        
        $userTypeMap = [
            'student' => 'users.student_id IS NOT NULL',
            'employee' => 'users.employee_id IS NOT NULL AND users.student_id IS NULL',
            'guest' => 'users.student_id IS NULL AND users.employee_id IS NULL'
        ];
        
        foreach ($userTypeMap as $userType => $condition) {
            // Get users of this type WHO HAD ENTRIES IN THE WEEK
            $userIds = DB::table('activity_logs')
                ->join('users', 'activity_logs.actor_id', '=', 'users.id')
                ->where('activity_logs.action', 'entry')
                ->where('activity_logs.actor_type', 'user')
                ->whereBetween('activity_logs.created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
                ->whereRaw($condition)
                ->distinct()
                ->pluck('activity_logs.actor_id');

            // Get vehicle counts for these users in the week
            $vehicleCounts = [];
            if ($userIds->count() > 0) {
                $vehicleCounts = DB::table('vehicles')
                    ->whereIn('user_id', $userIds)
                    ->selectRaw('type, COUNT(*) as count')
                    ->groupBy('type')
                    ->get()
                    ->keyBy('type')
                    ->toArray();
            }

            // Build complete breakdown with all vehicle types
            $completeBreakdown = [];
            foreach ($allVehicleTypes as $vehicleType) {
                $completeBreakdown[] = (object)[
                    'type' => $vehicleType,
                    'count' => isset($vehicleCounts[$vehicleType]) ? $vehicleCounts[$vehicleType]->count : 0
                ];
            }

            $this->userTypeVehicleBreakdown[$userType] = $completeBreakdown;
        }
    }

    private function calculateMonthlyBreakdowns($startDate, $endDate)
    {
        // Get all data for the month
        $monthData = DB::table('activity_logs')
            ->where('action', 'entry')
            ->where('actor_type', 'user')
            ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->get();

        // Calculate vehicle type breakdown
        $this->calculateMonthlyVehicleTypeBreakdown($monthData);
        
        // Calculate user type breakdown
        $this->calculateMonthlyUserTypeBreakdown($startDate, $endDate);
        
        // Calculate parking area breakdown
        $this->calculateMonthlyParkingAreaBreakdown($startDate, $endDate);
        
        // Calculate time ranges
        $this->calculateMonthlyTimeRanges($monthData);
        
        // Calculate user type vehicle breakdown
        $this->calculateMonthlyUserTypeVehicleBreakdown($startDate, $endDate);
    }

    private function calculateMonthlyVehicleTypeBreakdown($monthData)
    {
        // Get all vehicle types from system
        $allVehicleTypes = DB::table('vehicles')
            ->distinct('type')
            ->pluck('type');

        // Count vehicles in entries
        $vehicleCounts = [];
        $totalVehicles = 0;

        foreach ($monthData as $entry) {
            $userVehicles = DB::table('vehicles')
                ->where('user_id', $entry->actor_id)
                ->pluck('type');

            foreach ($userVehicles as $type) {
                $vehicleCounts[$type] = ($vehicleCounts[$type] ?? 0) + 1;
                $totalVehicles++;
            }
        }

        // Build breakdown with all vehicle types
        $this->vehicleTypeBreakdown = [];
        foreach ($allVehicleTypes as $type) {
            $count = $vehicleCounts[$type] ?? 0;
            $percentage = $totalVehicles > 0 ? round(($count / $totalVehicles) * 100, 1) : 0;

            $this->vehicleTypeBreakdown[] = (object)[
                'type' => $type,
                'count' => $count,
                'percentage' => $percentage
            ];
        }
    }

    private function calculateMonthlyUserTypeBreakdown($startDate, $endDate)
    {
        $userTypeCounts = DB::table('activity_logs')
            ->selectRaw("
                CASE
                    WHEN users.student_id IS NOT NULL THEN 'Student'
                    WHEN users.employee_id IS NOT NULL THEN 'Employee'
                    ELSE 'Guest'
                END as user_type,
                COUNT(*) as count
            ")
            ->leftJoin('users', 'activity_logs.actor_id', '=', 'users.id')
            ->where('activity_logs.action', 'entry')
            ->where('activity_logs.actor_type', 'user')
            ->whereBetween('activity_logs.created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->groupBy('user_type')
            ->get()
            ->keyBy('user_type')
            ->toArray();

        $this->userTypeBreakdown = [];
        $userTypes = ['Student', 'Employee', 'Guest'];
        $totalUsers = array_sum(array_map(fn($item) => $item->count, $userTypeCounts));

        foreach ($userTypes as $type) {
            $count = isset($userTypeCounts[$type]) ? $userTypeCounts[$type]->count : 0;
            $percentage = $totalUsers > 0 ? round(($count / $totalUsers) * 100, 1) : 0;

            $this->userTypeBreakdown[] = (object)[
                'type' => $type,
                'count' => $count,
                'percentage' => $percentage
            ];
        }
    }

    private function calculateMonthlyParkingAreaBreakdown($startDate, $endDate)
    {
        // Get all active parking areas
        $allAreas = DB::table('parking_areas')->whereNull('deleted_at')->select('id', 'name')->get();

        // Get entry counts by area for this month (only from active areas)
        $activeAreaIds = $allAreas->pluck('id')->toArray();
        $areaEntryCounts = DB::table('activity_logs')
            ->where('action', 'entry')
            ->where('actor_type', 'user')
            ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->whereIn('area_id', $activeAreaIds)
            ->selectRaw('area_id, COUNT(*) as count')
            ->groupBy('area_id')
            ->get()
            ->keyBy('area_id')
            ->toArray();

        // Build complete breakdown with all active areas
        $this->parkingAreaBreakdown = [];

        // Add Main Gate (entries with NULL area_id)
        $mainGateCount = DB::table('activity_logs')
            ->where('action', 'entry')
            ->where('actor_type', 'user')
            ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->whereNull('area_id')
            ->count();

        $this->parkingAreaBreakdown[] = (object)[
            'name' => 'Main Gate',
            'count' => $mainGateCount
        ];

        // Add all active parking areas
        foreach ($allAreas as $area) {
            $this->parkingAreaBreakdown[] = (object)[
                'name' => $area->name,
                'count' => isset($areaEntryCounts[$area->id]) ? $areaEntryCounts[$area->id]->count : 0
            ];
        }
    }

    private function calculateMonthlyTimeRanges($monthData)
    {
        // Calculate entries by time range for the month
        $this->timeRanges = [
            ['name' => 'Morning', 'label' => 'Morning (06:00-12:00)', 'start' => 6, 'end' => 12, 'count' => 0],
            ['name' => 'Afternoon', 'label' => 'Afternoon (12:00-18:00)', 'start' => 12, 'end' => 18, 'count' => 0],
            ['name' => 'Evening', 'label' => 'Evening (18:00-00:00)', 'start' => 18, 'end' => 24, 'count' => 0],
            ['name' => 'Night', 'label' => 'Night (00:00-06:00)', 'start' => 0, 'end' => 6, 'count' => 0],
        ];

        foreach ($monthData as $entry) {
            $hour = Carbon::parse($entry->created_at)->hour;

            foreach ($this->timeRanges as &$range) {
                if ($hour >= $range['start'] && $hour < $range['end']) {
                    $range['count']++;
                    break;
                }
            }
        }
    }

    private function calculateMonthlyUserTypeVehicleBreakdown($startDate, $endDate)
    {
        // Get all vehicle types from system
        $allVehicleTypes = DB::table('vehicles')
            ->distinct('type')
            ->pluck('type')
            ->toArray();

        // Get user types with their vehicles for the month
        $this->userTypeVehicleBreakdown = [];
        
        $userTypeMap = [
            'student' => 'users.student_id IS NOT NULL',
            'employee' => 'users.employee_id IS NOT NULL AND users.student_id IS NULL',
            'guest' => 'users.student_id IS NULL AND users.employee_id IS NULL'
        ];
        
        foreach ($userTypeMap as $userType => $condition) {
            // Get users of this type WHO HAD ENTRIES IN THE MONTH
            $userIds = DB::table('activity_logs')
                ->join('users', 'activity_logs.actor_id', '=', 'users.id')
                ->where('activity_logs.action', 'entry')
                ->where('activity_logs.actor_type', 'user')
                ->whereBetween('activity_logs.created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
                ->whereRaw($condition)
                ->distinct()
                ->pluck('activity_logs.actor_id');

            // Get vehicle counts for these users in the month
            $vehicleCounts = [];
            if ($userIds->count() > 0) {
                $vehicleCounts = DB::table('vehicles')
                    ->whereIn('user_id', $userIds)
                    ->selectRaw('type, COUNT(*) as count')
                    ->groupBy('type')
                    ->get()
                    ->keyBy('type')
                    ->toArray();
            }

            // Build complete breakdown with all vehicle types
            $completeBreakdown = [];
            foreach ($allVehicleTypes as $vehicleType) {
                $completeBreakdown[] = (object)[
                    'type' => $vehicleType,
                    'count' => isset($vehicleCounts[$vehicleType]) ? $vehicleCounts[$vehicleType]->count : 0
                ];
            }

            $this->userTypeVehicleBreakdown[$userType] = $completeBreakdown;
        }
    }

    public function render()
    {
        return view('livewire.admin.analytics-chart-component');
    }
}