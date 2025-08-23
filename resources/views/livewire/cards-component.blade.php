<div wire:poll.5s class="d-flex justify-content-between gap-3 cards-container">
    <div wire:click="goTo('/parking-slots')" class="card  card-1" style="cursor:pointer;">
        <div class="card-body">
            <h1 class="card-title">{{ $totalSlots }}</h1>
            <p class="card-text">Total Parking Slots.</p>
        </div>
        <div class="card-footer">
            More Info ➜
        </div>
    </div>

    <div wire:click="goTo('/users')" class="card card-2" style="cursor:pointer;">
        <div class="card-body">
            <h1 class="card-title">{{ $totalUsers }}</h1>
            <p class="card-text">Total Users.</p>
        </div>
        <div class="card-footer">
            More Info ➜
        </div>
    </div>

    <div class="card card-3" style="cursor:pointer;">
        <div class="card-body">
            <h1 class="card-title">{{ $totalStatus1 }}</h1>
            <p class="card-text">Total Parking (Occupied).</p>
        </div>
        <div class="card-footer">
            More Info ➜
        </div>
    </div>
<div class="card card-4">
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
</div>



</div>
