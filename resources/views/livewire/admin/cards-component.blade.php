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
<div onclick="window.location='{{ url('/activity-log?activeTab=entry/exit') }}'" class="card card-3" style="cursor:pointer;">
    <div class="card-body">
        <h2 class="card-title">Campus Activity</h2>

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


    {{-- Analytics Dashboard --}}
    <div class="card card-5" style="cursor:pointer;">
        <a href="{{ url('/dashboard/analytics-dashboard') }}" style="text-decoration: none; color: white;">
        <div class="card-body">
            <h1 class="card-title">Analytics Dashboard</h1>
            <p class="card-text">Wala pang design.</p>
        </div>
    </a>
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
