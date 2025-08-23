<div wire:poll.500ms="pollEpc" 
     class="d-flex flex-column justify-content-center align-items-center w-100 h-100 gap-4 p-4 overflow-auto">

    @foreach ($scans as $index => $scan)
        @php
            $status = $scan['status'] ?? 'OUT';
            $colorClass = $status === 'IN' ? 'text-success' : ($status === 'OUT' ? 'text-danger' : 'text-secondary');
        @endphp

        <div class="d-flex align-items-center border rounded shadow-sm bg-white" 
             style="font-size: {{ $loop->first ? '1.8rem' : '1.3rem' }}; 
                    width: 100%; max-width: {{ $loop->first ? '1200px' : '900px' }};
                    padding: {{ $loop->first ? '2.5rem' : '1rem' }};
                    transition: transform 0.3s ease;">

            <!-- Picture -->
            <img src="{{ $scan['picture'] }}" 
                 alt="Profile" 
                 class="rounded-circle me-4"
                 style="width: {{ $loop->first ? '220px' : '100px' }};
                        height: {{ $loop->first ? '220px' : '100px' }};
                        object-fit: cover;">

            <!-- Texts -->
            <div>
                <div class="fw-bold" 
                     style="font-size: {{ $loop->first ? '2.2rem' : '1.5rem' }};">
                    {{ $scan['name'] ?? 'Name, EPC' }}
                </div>
                <div class="fw-bold {{ $colorClass }}" 
                     style="font-size: {{ $loop->first ? '2rem' : '1.2rem' }};">
                    {{ $status }}
                </div>
            </div>
        </div>
    @endforeach
</div>
