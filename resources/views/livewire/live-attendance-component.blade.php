<div wire:poll.100ms="pollEpc" class="d-flex justify-content-center align-items-center w-100 h-100">
    <div class="d-flex align-items-center p-3 border rounded shadow-sm bg-white">
        <!-- Column 1: Picture -->
        <img src="{{ asset('images/placeholder.jpg') }}" 
             alt="Profile" 
             class="rounded-circle me-3"
             style="width: 60px; height: 60px; object-fit: cover;">

        <!-- Column 2: Texts -->
        <div>
            {{ $latestEpc ?? 'None yet' }}
            <div class="text-muted">Checked in at 9:42 AM</div>
        </div>
    </div>
</div>
