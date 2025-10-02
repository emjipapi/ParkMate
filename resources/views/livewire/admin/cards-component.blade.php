<div wire:poll.5s class="cards-container align-items-end">

    {{-- Car Slots --}}
    <div onclick="window.location='{{ url('/parking-slots') }}'" class="card card-1" style="cursor:pointer;">
        <div class="card-body">
            <h1 class="card-title">{{ $totalCarSlots }}</h1>
            <p class="card-text">Total Car Slots</p>
            <small>{{ $totalCarOccupied }} Occupied</small>
        </div>
        <div class="card-footer">More Info ➜</div>
    </div>

    {{-- Motorcycle Slots --}}
    <div onclick="window.location='{{ url('/parking-slots') }}'" class="card card-2" style="cursor:pointer;">
        <div class="card-body">
            <h1 class="card-title">{{ $totalMotoSlots }}</h1>
            <p class="card-text">Total Motorcycle Slots</p>
            <small>{{ $totalMotoOccupied }} Occupied</small>

        </div>
        <div class="card-footer">More Info ➜</div>
    </div>

    {{-- Campus Entry/Exit Summary --}}
    <div onclick="window.location='{{ url('/activity-log?activeTab=entry/exit') }}'" class="card card-3"
        style="cursor:pointer;">
        <div class="card-body">
            <h2 class="card-title" 
    style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
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
    </div>
<div class="card card-6 text-center d-flex flex-column justify-content-center align-items-center" 
     style="cursor:pointer;" 
     onclick="window.location='{{ url('/create-report') }}'">
    <div class="card-body d-flex flex-column justify-content-center align-items-center">
        <i class="bi bi-plus-circle-fill" style="font-size: 5rem; color: white;"></i>
        <p class="card-text mt-2 mb-1 fw-bold">Make Report</p>
    </div>
</div>

{{-- Analytics Dashboard --}}
<div onclick="window.location='{{ url('/dashboard/analytics-dashboard') }}'" class="card card-5 d-flex flex-column"
    style="cursor:pointer;">
    <div class="card-body d-flex flex-column justify-content-center align-items-center" style="padding: 0.5rem;">
        <i class="bi bi-bar-chart" style="font-size: 5rem; color: white; line-height: 1; display: block;"></i>
        <h1 style="margin: 0.5rem 0 0 0; font-size: 1.5rem; font-weight: bold;">Analytics Dashboard</h1>
    </div>
    <div class="card-footer">More Info ➜</div>
</div>

    {{-- Activity Logs --}}
    <div class="card card-4">
        <a href="{{ url('/activity-log') }}" style="text-decoration: none; color: black;">
            <div class="card-body">
                <h5>Recent Activity</h5>
                @forelse ($recentActivities as $activity)
                <div class="recent-activity-item mb-2">
                    {{ $activity->details }}
                </div>
                @empty
                <p>No recent activity.</p>
                @endforelse
            </div>
        </a>
    </div>
</div>