<div wire:poll.500ms="pollEpc" 
     class="d-flex flex-column justify-content-center align-items-center w-100 h-100 gap-4 p-4 overflow-auto">

    @foreach ($scans as $index => $scan)
        @php
            $status = $scan['status'] ?? 'OUT';
            
            // Set color class based on status
            if ($status === 'IN') {
                $colorClass = 'text-success';
                $bgClass = 'border-success';
            } elseif ($status === 'OUT') {
                $colorClass = 'text-danger';
                $bgClass = 'border-danger';
            } elseif ($status === 'DENIED') {
                $colorClass = 'text-warning';
                $bgClass = 'border-warning';
            } else {
                $colorClass = 'text-secondary';
                $bgClass = 'border-secondary';
            }
        @endphp

        <div class="d-flex align-items-center border rounded shadow-sm bg-white {{ $bgClass }}" 
             style="font-size: {{ $loop->first ? '1.8rem' : '1.3rem' }}; 
                    width: 100%; max-width: {{ $loop->first ? '1200px' : '900px' }};
                    padding: {{ $loop->first ? '2.5rem' : '1rem' }};
                    transition: transform 0.3s ease;
                    {{ $status === 'DENIED' ? 'background: linear-gradient(135deg, #ffffffff 0%, #fff3cd 100%);' : '' }}">

            <!-- Picture -->
            <img src="{{ $scan['picture'] }}" 
                 alt="Profile" 
                 class="rounded-circle me-4 {{ $status === 'DENIED' ? 'opacity-75' : '' }}"
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
                    @if ($status === 'DENIED')
                        <small class="d-block text-muted" style="font-size: 0.7em; font-weight: normal;">
                            Entry denied due to violations
                        </small>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</div>