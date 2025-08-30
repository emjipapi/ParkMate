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
{{-- Violations & Pending Reports --}}
<div onclick="window.location='{{ url('/user-violation-tracking') }}'" class="card card-3" style="cursor:pointer;">
    <div class="card-body">
        <h1 class="card-title">{{ $myViolationsCount }}</h1>
        <p class="card-text">Violations</p>
        <small>{{ $myPendingReports }} Reports Pending</small>
    </div>
    <div class="card-footer">More Info ➜</div>
</div>

<div onclick="window.location='{{ url('/user-violation-tracking') }}'" class="card card-5 text-center" style="cursor:pointer;">
    <div class="card-body d-flex flex-column justify-content-center align-items-center">
        <i class="fas fa-plus-circle" style="font-size: 4rem; color: white;"></i>
        <p class="card-text mt-2 mb-1 fw-bold">Make Report</p>
    </div>
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
