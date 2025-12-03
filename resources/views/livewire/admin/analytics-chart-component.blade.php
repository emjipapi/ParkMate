{{-- resources\views\livewire\admin\analytics-chart-component.blade.php --}}
<div class="w-full" style="max-width: 1200px; margin: auto;">
    <div class="w-full" style="max-width: 1200px; margin: auto;">
<!-- Filters Row -->
<div class="d-flex flex-column flex-md-row align-items-start align-items-md-center gap-3 mb-3" wire:loading.class="opacity-50">
        <!-- Chart Type Selector -->
    <div class="d-flex flex-column">
        <label for="chartType" class="form-label mb-1 text-sm">Chart Type:</label>
        <select 
            id="chartType" 
            wire:model.change="chartType"
            class="form-select form-select-sm w-100 w-md-auto"
            style="max-width: 200px;"
            wire:loading.attr="disabled" 
            wire:target="selectedDate,chartType">
            <option value="entries">Entry Analytics</option>
            <option value="duration">Average Duration of Stays</option>
            <option value="logins">User Logins</option>
            <option value="admin_logins">Admin Logins</option>
        </select>
    </div>

    <!-- Period Selector (for Entry Analytics and Average Duration of Stays) -->
    @if($chartType === 'entries' || $chartType === 'duration')
    <div class="d-flex flex-column">
        <label for="period" class="form-label mb-1 text-sm">Period:</label>
        <select 
            id="period" 
            wire:model.change="period"
            class="form-select form-select-sm w-100 w-md-auto"
            style="max-width: 200px;"
            wire:loading.attr="disabled" 
            wire:target="selectedDate,chartType,period">
            <option value="daily">Daily</option>
            <option value="weekly">Weekly</option>
            <option value="monthly">Monthly</option>
        </select>
    </div>
    @endif
    <!-- Date Selector -->
    <div class="d-flex flex-column">
        @if(($chartType === 'entries' || $chartType === 'duration') && $period === 'weekly')
            <label for="dateSelect" class="form-label mb-1 text-sm">Week:</label>
            <input 
                type="week" 
                id="dateSelect" 
                wire:model.change="selectedDate" 
                class="form-control form-control-sm w-100 w-md-auto"
                style="max-width: 200px;"
                wire:loading.attr="disabled"
                wire:target="selectedDate,chartType,period">
        @elseif(($chartType === 'entries' || $chartType === 'duration') && $period === 'monthly')
            <label for="dateSelect" class="form-label mb-1 text-sm">Month:</label>
            <input 
                type="month" 
                id="dateSelect" 
                wire:model.change="selectedDate" 
                class="form-control form-control-sm w-100 w-md-auto"
                style="max-width: 200px;"
                wire:loading.attr="disabled"
                wire:target="selectedDate,chartType,period">
        @else
            <label for="dateSelect" class="form-label mb-1 text-sm">Date:</label>
            <input 
                type="date" 
                id="dateSelect" 
                wire:model.change="selectedDate" 
                class="form-control form-control-sm w-100 w-md-auto"
                style="max-width: 200px;"
                wire:loading.attr="disabled"
                wire:target="selectedDate,chartType"
                min="{{ min($dates) }}" 
                max="{{ max($dates) }}"
                onfocus="this.showPicker();" 
                onmousedown="event.preventDefault(); this.showPicker();">
        @endif
    </div>
</div>


        <!-- Chart Container -->
        <div class="h-96 w-full" style="height: 500px;">
            <canvas x-data="chartComponent()" x-ref="canvas" wire:ignore></canvas>
        </div>
        <p class="text-muted text-center d-block d-md-none mt-2" style="font-size: 0.9rem;">
    📊 Best viewed on desktop for full chart details.
</p>

        <!-- Summary Statistics Cards (only show for Entry Analytics Daily) -->
        @if($chartType === 'entries' && $period === 'daily')
        <div class="mt-5">
            <h5 class="mb-3">Summary Statistics</h5>
            <div class="row g-3">
                <!-- Peak Hour -->
                @if($peakHour)
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-muted">Peak Hour</h6>
                            <p class="card-text">
                                Busiest time: <strong>{{ $peakHour['formatted'] }}</strong> with <strong>{{ $peakHour['count'] }}</strong> entries
                            </p>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Total Entries -->
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-muted">Total Entries</h6>
                            <p class="card-text">
                                Total entries for the day: <strong>{{ $totalEntries }}</strong>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Quietest Hour -->
                @if($quietestHour)
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-muted">Quietest Hour</h6>
                            <p class="card-text">
                                Least busy: <strong>{{ $quietestHour['formatted'] }}</strong> with <strong>{{ $quietestHour['count'] }}</strong> entries
                            </p>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Average Per Hour -->
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-muted">Average Per Hour</h6>
                            <p class="card-text">
                                Average: <strong>{{ $averagePerHour }}</strong> entries per hour
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Busiest Period -->
                @if($busyPeriod)
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-muted">Busiest Period</h6>
                            <p class="card-text">
                                Peak period: <strong>{{ $busyPeriod['start'] }}-{{ $busyPeriod['end'] }}</strong> ({{ $busyPeriod['name'] }})
                            </p>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
        @elseif($chartType === 'entries' && $period === 'weekly')
        <!-- Summary Statistics Cards for Weekly -->
        <div class="mt-5">
            <h5 class="mb-3">Summary Statistics</h5>
            <div class="row g-3">
                <!-- Peak Day -->
                @if($peakDay)
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-muted">Peak Day</h6>
                            <p class="card-text">
                                Busiest day: <strong>{{ $peakDay['day'] }}</strong> ({{ $peakDay['date'] }})<br>
                                {{ $peakDay['count'] }} entries
                            </p>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Total Entries -->
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-muted">Total Entries</h6>
                            <p class="card-text">
                                Total entries for the week: <strong>{{ $totalEntries }}</strong>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Quietest Day -->
                @if($quietestDay)
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-muted">Quietest Day</h6>
                            <p class="card-text">
                                Least busy: <strong>{{ $quietestDay['day'] }}</strong> ({{ $quietestDay['date'] }})<br>
                                {{ $quietestDay['count'] }} entries
                            </p>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Average Per Day -->
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-muted">Average Per Day</h6>
                            <p class="card-text">
                                Average: <strong>{{ $averagePerDay }}</strong> entries per day
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Busiest Day -->
                @if($busiestDay)
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-muted">Busiest Day</h6>
                            <p class="card-text">
                                Most entries: <strong>{{ $busiestDay['day'] }}</strong> ({{ $busiestDay['date'] }})<br>
                                {{ $busiestDay['count'] }} entries
                            </p>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Summary Statistics Cards for Monthly -->
        @if($chartType === 'entries' && $period === 'monthly')
        <div class="mt-5">
            <h5 class="mb-3">Summary Statistics</h5>
            <div class="row g-3">
                <!-- Peak Date -->
                @if($peakDateMonth)
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-muted">Peak Date</h6>
                            <p class="card-text">
                                Busiest date: <strong>{{ $peakDateMonth['day'] }}</strong> ({{ $peakDateMonth['date'] }})<br>
                                {{ $peakDateMonth['count'] }} entries
                            </p>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Total Entries -->
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-muted">Total Entries</h6>
                            <p class="card-text">
                                Total entries for the month: <strong>{{ $totalEntries }}</strong>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Quietest Date -->
                @if($quietestDateMonth)
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-muted">Quietest Date</h6>
                            <p class="card-text">
                                Least busy: <strong>{{ $quietestDateMonth['day'] }}</strong> ({{ $quietestDateMonth['date'] }})<br>
                                {{ $quietestDateMonth['count'] }} entries
                            </p>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Average Per Date -->
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-muted">Average Per Date</h6>
                            <p class="card-text">
                                Average: <strong>{{ $averagePerDateMonth }}</strong> entries per date
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Busiest Date -->
                @if($busiestDateMonth)
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-muted">Busiest Date</h6>
                            <p class="card-text">
                                Most entries: <strong>{{ $busiestDateMonth['day'] }}</strong> ({{ $busiestDateMonth['date'] }})<br>
                                {{ $busiestDateMonth['count'] }} entries
                            </p>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Summary Statistics Cards for Duration (Daily) -->
        @if($chartType === 'duration' && $period === 'daily')
        <div class="mt-5">
            <h5 class="mb-3">Summary Statistics</h5>
            <div class="row g-3">
                <!-- Average Duration Overall -->
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-muted">Average Duration</h6>
                            <p class="card-text">
                                Average stay: <strong>{{ $averageDurationOverall }}</strong> minutes
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Longest Duration -->
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-muted">Longest Duration</h6>
                            <p class="card-text">
                                Longest stay: <strong>{{ $longestDuration }}</strong> minutes
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Shortest Duration -->
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-muted">Shortest Duration</h6>
                            <p class="card-text">
                                Shortest stay: <strong>{{ $shortestDuration }}</strong> minutes
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Busiest Duration Hour -->
                @if($busiestDurationPeriod)
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-muted">Longest Stay Hour</h6>
                            <p class="card-text">
                                Hour with longest average: <strong>{{ $busiestDurationPeriod['formatted'] }}</strong><br>
                                {{ $busiestDurationPeriod['duration'] }} minutes
                            </p>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Quietest Duration Hour -->
                @if($quietestDurationPeriod)
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-muted">Shortest Stay Hour</h6>
                            <p class="card-text">
                                Hour with shortest average: <strong>{{ $quietestDurationPeriod['formatted'] }}</strong><br>
                                {{ $quietestDurationPeriod['duration'] }} minutes
                            </p>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
        @elseif($chartType === 'duration' && $period === 'weekly')
        <!-- Summary Statistics Cards for Duration (Weekly) -->
        <div class="mt-5">
            <h5 class="mb-3">Summary Statistics</h5>
            <div class="row g-3">
                <!-- Average Duration Overall -->
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-muted">Average Duration</h6>
                            <p class="card-text">
                                Average stay: <strong>{{ $averageDurationOverall }}</strong> minutes
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Longest Duration -->
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-muted">Longest Duration</h6>
                            <p class="card-text">
                                Longest stay: <strong>{{ $longestDuration }}</strong> minutes
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Shortest Duration -->
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-muted">Shortest Duration</h6>
                            <p class="card-text">
                                Shortest stay: <strong>{{ $shortestDuration }}</strong> minutes
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Busiest Duration Day -->
                @if($busiestDurationPeriod)
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-muted">Longest Stay Day</h6>
                            <p class="card-text">
                                Day with longest average: <strong>{{ $busiestDurationPeriod['day'] }}</strong> ({{ $busiestDurationPeriod['date'] }})<br>
                                {{ $busiestDurationPeriod['duration'] }} minutes
                            </p>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Quietest Duration Day -->
                @if($quietestDurationPeriod)
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-muted">Shortest Stay Day</h6>
                            <p class="card-text">
                                Day with shortest average: <strong>{{ $quietestDurationPeriod['day'] }}</strong> ({{ $quietestDurationPeriod['date'] }})<br>
                                {{ $quietestDurationPeriod['duration'] }} minutes
                            </p>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
        @elseif($chartType === 'duration' && $period === 'monthly')
        <!-- Summary Statistics Cards for Duration (Monthly) -->
        <div class="mt-5">
            <h5 class="mb-3">Summary Statistics</h5>
            <div class="row g-3">
                <!-- Average Duration Overall -->
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-muted">Average Duration</h6>
                            <p class="card-text">
                                Average stay: <strong>{{ $averageDurationOverall }}</strong> minutes
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Longest Duration -->
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-muted">Longest Duration</h6>
                            <p class="card-text">
                                Longest stay: <strong>{{ $longestDuration }}</strong> minutes
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Shortest Duration -->
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-muted">Shortest Duration</h6>
                            <p class="card-text">
                                Shortest stay: <strong>{{ $shortestDuration }}</strong> minutes
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Busiest Duration Date -->
                @if($busiestDurationPeriod)
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-muted">Longest Stay Date</h6>
                            <p class="card-text">
                                Date with longest average: <strong>{{ $busiestDurationPeriod['day'] }}</strong> ({{ $busiestDurationPeriod['date'] }})<br>
                                {{ $busiestDurationPeriod['duration'] }} minutes
                            </p>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Quietest Duration Date -->
                @if($quietestDurationPeriod)
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-muted">Shortest Stay Date</h6>
                            <p class="card-text">
                                Date with shortest average: <strong>{{ $quietestDurationPeriod['day'] }}</strong> ({{ $quietestDurationPeriod['date'] }})<br>
                                {{ $quietestDurationPeriod['duration'] }} minutes
                            </p>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Breakdown Section (Daily Only) -->
        @if($chartType === 'entries' && $period === 'daily')
        <div class="mt-5">
            <h5 class="mb-3 mt-4">Breakdown</h5>
            <div class="row g-3">
                <!-- Vehicle Type Breakdown -->
                @if(count($vehicleTypeBreakdown) > 0)
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-muted">Vehicle Type</h6>
                            @php
                                $totalVehicles = collect($vehicleTypeBreakdown)->sum('count');
                            @endphp
                            <p class="card-text small">
                                @foreach($vehicleTypeBreakdown as $vehicle)
                                    @php
                                        $percentage = $totalVehicles > 0 ? round(($vehicle->count / $totalVehicles) * 100, 1) : 0;
                                    @endphp
                                    <strong>{{ ucfirst($vehicle->type) }}</strong>: {{ $vehicle->count }} ({{ $percentage }}%)
                                    @if(!$loop->last)<br>@endif
                                @endforeach
                            </p>
                        </div>
                    </div>
                </div>
                @endif

                <!-- User Type Breakdown -->
                @if(count($userTypeBreakdown) > 0)
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-muted">User Type</h6>
                            <p class="card-text small">
                                @foreach($userTypeBreakdown as $user)
                                    @php
                                        $percentage = $totalEntries > 0 ? round(($user->count / $totalEntries) * 100, 1) : 0;
                                    @endphp
                                    <strong>{{ ucfirst($user->user_type) }}</strong>: {{ $user->count }} ({{ $percentage }}%)
                                    @if(!$loop->last)<br>@endif
                                @endforeach
                            </p>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Parking Area Breakdown -->
                @if(count($parkingAreaBreakdown) > 0)
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-muted">Parking Area</h6>
                            <p class="card-text small">
                                @foreach($parkingAreaBreakdown as $area)
                                    <strong>{{ $area->name }}</strong>: {{ $area->count }} entries
                                    @if(!$loop->last)<br>@endif
                                @endforeach
                            </p>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <!-- User Type Vehicle Breakdown Section -->
            @if(count($userTypeVehicleBreakdown) > 0)
            <h5 class="mb-3 mt-4">Vehicle Types by User</h5>
            <div class="row g-3">
                @foreach($userTypeVehicleBreakdown as $userType => $vehicles)
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-muted">{{ ucfirst($userType) }} Vehicles</h6>
                            <p class="card-text small">
                                @foreach($vehicles as $vehicle)
                                    <strong>{{ ucfirst($vehicle->type) }}</strong>: {{ $vehicle->count }}
                                    @if(!$loop->last)<br>@endif
                                @endforeach
                            </p>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif

            <!-- Time Ranges Section -->
            @if(count($timeRanges) > 0)
            <h5 class="mb-3 mt-4">Time Ranges</h5>
            <div class="row g-3">
                @foreach($timeRanges as $range)
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-muted">{{ $range['label'] }}</h6>
                            <p class="card-text">
                                <strong>{{ $range['count'] }}</strong> entries
                            </p>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
        @endif

        <!-- Breakdown Section (Weekly) -->
        @if($chartType === 'entries' && $period === 'weekly')
        <div class="mt-5">
            <h5 class="mb-3 mt-4">Breakdown</h5>
            <div class="row g-3">
                <!-- Vehicle Type Breakdown -->
                @if(count($vehicleTypeBreakdown) > 0)
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-muted">Vehicle Type</h6>
                            <p class="card-text small">
                                @foreach($vehicleTypeBreakdown as $vehicle)
                                    <strong>{{ ucfirst($vehicle->type) }}</strong>: {{ $vehicle->count }} ({{ $vehicle->percentage }}%)
                                    @if(!$loop->last)<br>@endif
                                @endforeach
                            </p>
                        </div>
                    </div>
                </div>
                @endif

                <!-- User Type Breakdown -->
                @if(count($userTypeBreakdown) > 0)
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-muted">User Type</h6>
                            <p class="card-text small">
                                @foreach($userTypeBreakdown as $user)
                                    <strong>{{ ucfirst($user->type) }}</strong>: {{ $user->count }} ({{ $user->percentage }}%)
                                    @if(!$loop->last)<br>@endif
                                @endforeach
                            </p>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Parking Area Breakdown -->
                @if(count($parkingAreaBreakdown) > 0)
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-muted">Parking Area</h6>
                            <p class="card-text small">
                                @foreach($parkingAreaBreakdown as $area)
                                    <strong>{{ $area->name }}</strong>: {{ $area->count }} entries
                                    @if(!$loop->last)<br>@endif
                                @endforeach
                            </p>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <!-- Time Ranges Section -->
            @if(count($timeRanges) > 0)
            <h5 class="mb-3 mt-4">Time Ranges</h5>
            <div class="row g-3">
                @foreach($timeRanges as $range)
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-muted">{{ $range['label'] }}</h6>
                            <p class="card-text">
                                <strong>{{ $range['count'] }}</strong> entries
                            </p>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
        @endif

        <!-- Vehicle Type Vehicle Breakdown Section (Weekly) -->
        @if($chartType === 'entries' && $period === 'weekly' && count($userTypeVehicleBreakdown) > 0)
        <div class="mt-5">
            <h5 class="mb-3 mt-4">Vehicle Types by User</h5>
            <div class="row g-3">
                @foreach($userTypeVehicleBreakdown as $userType => $vehicles)
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-muted">{{ ucfirst($userType) }} Vehicles</h6>
                            <p class="card-text small">
                                @foreach($vehicles as $vehicle)
                                    <strong>{{ ucfirst($vehicle->type) }}</strong>: {{ $vehicle->count }}
                                    @if(!$loop->last)<br>@endif
                                @endforeach
                            </p>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Breakdown Section (Monthly) -->
        @if($chartType === 'entries' && $period === 'monthly')
        <div class="mt-5">
            <h5 class="mb-3 mt-4">Breakdown</h5>
            <div class="row g-3">
                <!-- Vehicle Type Breakdown -->
                @if(count($vehicleTypeBreakdown) > 0)
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-muted">Vehicle Type</h6>
                            <p class="card-text small">
                                @foreach($vehicleTypeBreakdown as $vehicle)
                                    <strong>{{ ucfirst($vehicle->type) }}</strong>: {{ $vehicle->count }} ({{ $vehicle->percentage }}%)
                                    @if(!$loop->last)<br>@endif
                                @endforeach
                            </p>
                        </div>
                    </div>
                </div>
                @endif

                <!-- User Type Breakdown -->
                @if(count($userTypeBreakdown) > 0)
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-muted">User Type</h6>
                            <p class="card-text small">
                                @foreach($userTypeBreakdown as $user)
                                    <strong>{{ ucfirst($user->type) }}</strong>: {{ $user->count }} ({{ $user->percentage }}%)
                                    @if(!$loop->last)<br>@endif
                                @endforeach
                            </p>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Parking Area Breakdown -->
                @if(count($parkingAreaBreakdown) > 0)
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-muted">Parking Area</h6>
                            <p class="card-text small">
                                @foreach($parkingAreaBreakdown as $area)
                                    <strong>{{ $area->name }}</strong>: {{ $area->count }} entries
                                    @if(!$loop->last)<br>@endif
                                @endforeach
                            </p>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <!-- Time Ranges Section -->
            @if(count($timeRanges) > 0)
            <h5 class="mb-3 mt-4">Time Ranges</h5>
            <div class="row g-3">
                @foreach($timeRanges as $range)
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-muted">{{ $range['label'] }}</h6>
                            <p class="card-text">
                                <strong>{{ $range['count'] }}</strong> entries
                            </p>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif

            <!-- Vehicle Types by User Section -->
            @if(count($userTypeVehicleBreakdown) > 0)
            <h5 class="mb-3 mt-4">Vehicle Types by User</h5>
            <div class="row g-3">
                @foreach($userTypeVehicleBreakdown as $userType => $vehicles)
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-muted">{{ ucfirst($userType) }} Vehicles</h6>
                            <p class="card-text small">
                                @foreach($vehicles as $vehicle)
                                    <strong>{{ ucfirst($vehicle->type) }}</strong>: {{ $vehicle->count }}
                                    @if(!$loop->last)<br>@endif
                                @endforeach
                            </p>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
        @endif
    </div>

    <script>
        function chartComponent() {
            return {
                chart: null,
                isUpdating: false,
                updateTimeout: null,
                currentChartType: @json($chartType),
                eventListenerAdded: false,
                
                init() {
                    console.log('Chart component initializing...');
                    this.createChart();
                    this.setupEventListener();
                },

                createChart() {
                    try {
                        const ctx = this.$refs.canvas.getContext('2d');
                        if (!ctx) {
                            console.error('Failed to get canvas context');
                            return;
                        }

                        const initialLabels = @json($labels);
                        const initialData = @json($data);

                        console.log('Creating initial chart with:', { initialLabels, initialData });

                        this.chart = new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: initialLabels.length > 0 ? initialLabels : ['No Data'],
                                datasets: [{
                                    label: this.getDatasetLabel(this.currentChartType),
                                    data: initialData.length > 0 ? initialData : [0],
                                    borderColor: this.getBorderColor(this.currentChartType),
                                    backgroundColor: this.getBackgroundColor(this.currentChartType),
                                    borderWidth: 2
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                // animation: {
                                //     duration: 500
                                // },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        title: {
                                            display: true,
                                            text: this.getYAxisLabel(this.currentChartType)
                                        }
                                    }
                                }
                            }
                        });

                        console.log('Chart created successfully');
                    } catch (error) {
                        console.error('Error creating chart:', error);
                    }
                },

                setupEventListener() {
                    if (this.eventListenerAdded) {
                        console.log('Event listener already added, skipping');
                        return;
                    }

                    // Listen for chart updates
                    document.addEventListener('chartDataUpdated', (event) => {
                        console.log('Chart data updated event received:', event.detail);

                        if (this.updateTimeout) {
                            clearTimeout(this.updateTimeout);
                        }

                        this.updateTimeout = setTimeout(() => {
                            this.updateChart(event.detail);
                        }, 150);
                    });

                    this.eventListenerAdded = true;
                    console.log('Event listener added');
                },

                updateChart(eventData) {
                    console.log('updateChart called, isUpdating:', this.isUpdating);

                    if (this.isUpdating) {
                        console.log('Update skipped - already updating');
                        return;
                    }

                    this.isUpdating = true;
                    
                    // Disable controls immediately when update starts
                    this.disableControls();

                    try {
                        const data = eventData.data || [];
                        const labels = eventData.labels || [];
                        const chartType = eventData.chartType || 'entries';
                        this.currentChartType = chartType;

                        console.log('Updating chart with:', { 
                            labels, 
                            data, 
                            chartType,
                            labelsLength: labels.length,
                            dataLength: data.length
                        });

                        // Destroy existing chart properly with full cleanup
                        if (this.chart) {
                            console.log('Destroying existing chart');
                            try {
                                // Stop animations and rendering
                                this.chart.stop();
                                // Clear canvas manually before destroying
                                const ctx = this.$refs.canvas.getContext('2d');
                                if (ctx) {
                                    ctx.clearRect(0, 0, this.$refs.canvas.width, this.$refs.canvas.height);
                                }
                                // Now destroy
                                this.chart.destroy();
                                this.chart = null;
                            } catch (e) {
                                console.warn('Error during chart destruction:', e);
                                this.chart = null;
                            }
                        }

                        // Recreate chart after longer delay to ensure cleanup completes
                        setTimeout(() => {
                            try {
                                const ctx = this.$refs.canvas.getContext('2d');
                                if (!ctx) {
                                    console.error('Canvas context is null during update');
                                    this.isUpdating = false;
                                    return;
                                }

                                console.log('Recreating chart...');

                                this.chart = new Chart(ctx, {
                                    type: 'bar',
                                    data: {
                                        labels: labels.length > 0 ? labels : ['No Data'],
                                        datasets: [{
                                            label: this.getDatasetLabel(chartType),
                                            data: data.length > 0 ? data : [0],
                                            borderColor: this.getBorderColor(chartType),
                                            backgroundColor: this.getBackgroundColor(chartType),
                                            borderWidth: 2
                                        }]
                                    },
                                    options: {
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        // animation: {
                                        //     duration: 500
                                        // },
                                        scales: {
                                            y: {
                                                beginAtZero: true,
                                                title: {
                                                    display: true,
                                                    text: this.getYAxisLabel(chartType)
                                                }
                                            }
                                        }
                                    }
                                });

                                console.log('Chart recreated successfully');
                                this.isUpdating = false;

                            } catch (error) {
                                console.error('Error recreating chart:', error);
                                this.isUpdating = false;
                                this.enableControls(); // Re-enable on error
                            }
                        }, 200); // Increased from 100ms to 200ms

                    } catch (error) {
                        console.error('Error in updateChart:', error);
                        this.isUpdating = false;
                        this.enableControls(); // Re-enable on error
                    }
                },

                getDatasetLabel(chartType) {
                    const labels = {
                        'entries': 'Entry Analytics',
                        'duration': 'Avg Duration (minutes)',
                        'logins': 'User Logins',
                        'admin_logins': 'Admin Logins'
                    };
                    return labels[chartType] || 'Data';
                },

                getYAxisLabel(chartType) {
                    const labels = {
                        'entries': 'Number of Entries',
                        'duration': 'Minutes',
                        'logins': 'Number of Logins',
                        'admin_logins': 'Number of Logins'
                    };
                    return labels[chartType] || 'Value';
                },

                getBorderColor(chartType) {
                    const colors = {
                        'entries': 'rgba(75, 192, 192, 1)',
                        'duration': 'rgba(255, 99, 132, 1)',
                        'logins': 'rgba(54, 162, 235, 1)',
                        'admin_logins': 'rgba(255, 206, 86, 1)'
                    };
                    return colors[chartType] || 'rgba(0, 0, 0, 1)';
                },

                getBackgroundColor(chartType) {
                    const colors = {
                        'entries': 'rgba(75, 192, 192, 0.2)',
                        'duration': 'rgba(255, 99, 132, 0.2)',
                        'logins': 'rgba(54, 162, 235, 0.2)',
                        'admin_logins': 'rgba(255, 206, 86, 0.2)'
                    };
                    return colors[chartType] || 'rgba(0, 0, 0, 0.2)';
                },

                disableControls() {
                    const dateInput = document.getElementById('dateSelect');
                    const chartTypeSelect = document.getElementById('chartType');
                    const periodSelect = document.getElementById('period');
                    
                    if (dateInput) dateInput.disabled = true;
                    if (chartTypeSelect) chartTypeSelect.disabled = true;
                    if (periodSelect) periodSelect.disabled = true;

                    setTimeout(() => {
                        if (dateInput) dateInput.disabled = false;
                        if (chartTypeSelect) chartTypeSelect.disabled = false;
                        if (periodSelect) periodSelect.disabled = false;
                    }, 3000);
                },

                enableControls() {
                    const dateInput = document.getElementById('dateSelect');
                    const chartTypeSelect = document.getElementById('chartType');
                    const periodSelect = document.getElementById('period');
                    
                    if (dateInput) dateInput.disabled = false;
                    if (chartTypeSelect) chartTypeSelect.disabled = false;
                    if (periodSelect) periodSelect.disabled = false;
                }
            }
        }
    </script>
</div>