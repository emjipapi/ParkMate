<div wire:poll.100ms="pollEpc" 
     class="d-flex justify-content-center align-items-center w-100 h-100">
    <div class="d-flex align-items-center p-5 border rounded shadow-sm bg-white" 
         style="font-size: 2rem; width: 1500px;">
        <!-- Column 1: Picture -->
<img src="{{ $profilePicture }}" 
     alt="Profile" 
     class="rounded-circle me-5"
     style="width: 250px; height: 250px; object-fit: cover;">

        <!-- Column 2: Texts -->
        <div>
            <div class="fw-bold" style="font-size: 3.5rem;">
                {{ $latestEpc ?? 'Name, EPC' }}
            </div>
                        <div style="font-size: 3rem; color: {{ $status === 'IN' ? 'green' : ($status === 'OUT' ? 'red' : 'gray') }};">
                {{ $status ?? 'Status' }}
            </div>
        </div>
    </div>
</div>
