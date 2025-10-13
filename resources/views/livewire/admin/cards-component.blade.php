{{-- resources\views\livewire\admin\cards-component.blade.php --}}
<div wire:poll.5s class="cards-container align-items-end">

    {{-- Car Slots --}}
    <div @canaccess("parking_slots") onclick="window.location='{{ url('/parking-slots') }}'" @endcanaccess class="card card-1" style="cursor:pointer;">
        @canaccess("parking_slots")
        <div class="card-body">
            <h1 class="card-title">{{ $totalCarOccupied }}</h1>
            <p class="card-text">Occupied Car Slots</p>
            <small>{{ $totalCarSlots }} Total Motorcycle Slots</small>
        </div>
        <div class="card-footer">More Info ➜</div>
        @endcanaccess
    </div>

    {{-- Motorcycle Slots --}}
    <div @canaccess("parking_slots") onclick="window.location='{{ url('/parking-slots') }}'" @endcanaccess class="card card-2" style="cursor:pointer;">
        @canaccess("parking_slots")
        <div class="card-body">
            <h1 class="card-title">{{ $totalMotoOccupied }}</h1>
            <p class="card-text">Occupied Motorcycle Slots</p>
            <small>{{ $totalMotoSlots }} Total Motorcycle Slots</small>

        </div>
        <div class="card-footer">More Info ➜</div>
        @endcanaccess
    </div>

    {{-- Campus Entry/Exit Summary --}}
    <div @canaccess("entry_exit_logs") onclick="window.location='{{ url('/activity-log?activeTab=entry/exit') }}'" @endcanaccess class="card card-3"
        style="cursor:pointer;">
        @canaccess("entry_exit_logs")
        <div class="card-body">
            <h2 class="card-title" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                Campus Activity
            </h2>

            <p class="mb-1">
                <strong>{{ $currentlyInside }}</strong> Currently Inside Campus
            </p>
            <p class="mb-1">
                <strong>{{ $entryCount }}</strong> Entered Today
            </p>
            <p class="mb-0">
                <strong>{{ $exitCount }}</strong> Exited Today
            </p>
        </div>
        <div class="card-footer">More Info ➜</div>
        @endcanaccess
    </div>
    @canaccess("create_report")
    <div class="card card-6 text-center d-flex flex-column justify-content-center align-items-center"
        style="cursor:pointer;" onclick="window.location='{{ url('/create-report') }}'">
        <div class="card-body d-flex flex-column justify-content-center align-items-center">
            <i class="bi bi-plus-circle-fill" style="font-size: 5rem; color: white;"></i>
            <p class="card-text mt-2 mb-1 fw-bold">Make Report</p>
        </div>
    </div>
    @endcanaccess

    {{-- Analytics Dashboard --}}
    <div @canaccess("analytics_dashboard") onclick="window.location='{{ url('/admin-dashboard/analytics-dashboard') }}'" @endcanaccess class="card card-5 d-flex flex-column"
        style="cursor:pointer;">
        <div class="card-body d-flex flex-column justify-content-center align-items-center" style="padding: 0.5rem;">
            <i class="bi bi-bar-chart" style="font-size: 5rem; color: white; line-height: 1; display: block;"></i>
            <h1 style="margin: 0.5rem 0 0 0; font-size: 1.5rem; font-weight: bold;">Analytics Dashboard</h1>
        </div>
        <div class="card-footer">More Info ➜</div>
    </div>

    {{-- Activity Logs --}}
    <div class="card card-4">
        <a @canaccess("activity_log") href="{{ url('/activity-log') }}" @endcanaccess style="text-decoration: none; color: black;">
            <div class="card-body">
                <h5>Recent Activity</h5>
                @canaccess("activity_log")
                @forelse ($recentActivities as $activity)
                <div class="recent-activity-item mb-2">
                    {{ $activity->details }}
                </div>
                @empty
                <p>No recent activity.</p>
                @endforelse
                @endcanaccess
            </div>
        </a>
    </div>
</div>