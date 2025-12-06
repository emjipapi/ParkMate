{{-- resources\views\livewire\admin\parking-slots-component.blade.php --}}
<div class="container py-4">
    <h4 class="mb-2 mb-sm-0">Map</h4>
        <div class="live-map-viewport mb-4 mt-3" role="main">
        <div class="livewire-component-wrapper" id="livewire-map-root">

            <livewire:admin.parking-map-live-view-component :map-id="$defaultMapId" />

        </div>
    </div>
    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-start align-items-sm-center mb-4">
        <h4 class="mb-2 mb-sm-0">Parking Slots</h4>
        <div class="btn-group btn-group-sm ms-sm-auto" role="group" aria-label="Filter slots">
            <button type="button" class="btn btn-outline-secondary @if($filter === 'all') active @endif"
                wire:click="$set('filter','all')">All</button>
            <button type="button" class="btn btn-outline-secondary @if($filter === 'available') active @endif"
                wire:click="$set('filter','available')">Available</button>
            <button type="button" class="btn btn-outline-secondary @if($filter === 'occupied') active @endif"
                wire:click="$set('filter','occupied')">Occupied</button>
        </div>
    </div>


    <div class="accordion" id="areasAccordion" wire:ignore.self wire:poll.5s="refreshSlotData">
        @foreach($areas as $area)
        <div class="accordion-item mb-4" wire:ignore.self>
            <h2 class="accordion-header" id="heading-{{ $area['id'] }}">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                    data-bs-target="#collapse-{{ $area['id'] }}" aria-expanded="false"
                    aria-controls="collapse-{{ $area['id'] }}">


                    <div class="d-flex flex-column flex-sm-row w-100 area-info">
                        <!-- Left column: Area Name -->
                        <div class="flex-shrink-0 me-sm-3 mb-2 mb-sm-0">
                            <span class="fw-semibold">{{ $area['name'] }}</span>
                        </div>

                        <!-- Right column: car & motorcycle info stacked -->
                        <div class="d-flex flex-column">
                            @if(isset($area['car_slots']) && count($area['car_slots']) > 0)
                            <span class="text-muted">
                                🚗 {{ $area['car_total'] - $area['car_available'] }} Occupied / {{ $area['car_total'] }}
                                car slots
                            </span>
                            @endif
                            @if ((int) ($area['moto_total'] ?? 0) > 0)
                            <span class="text-muted">
                                🛵 {{ $area['moto_occupied_count'] }} Occupied / {{ $area['moto_total'] }} motorcycles
                            </span>
                            @endif
                        </div>
                    </div>

                </button>
            </h2>

            <div id="collapse-{{ $area['id'] }}" class="accordion-collapse collapse"
                aria-labelledby="heading-{{ $area['id'] }}" wire:ignore.self>
                <div class="accordion-body pt-3 pb-4">

                    <div class="mb-3">
                        <strong>Allowed Users:</strong>
                        <div class="mt-2 d-inline-flex gap-1">
                            @if($area['allow_students'])
                            <span class="badge bg-primary">Students</span>
                            @endif
                            @if($area['allow_employees'])
                            <span class="badge bg-success">Employees</span>
                            @endif
                            @if($area['allow_guests'])
                            <span class="badge bg-warning text-dark">Guests</span>
                            @endif
                        </div>
                    </div>

                    {{-- Motorcycles: counter style --}}
                    <div
                        class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-sm-between mb-4">
                        @if ((int) ($area['moto_total'] ?? 0) > 0)
                        <div
                            class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center gap-2 mb-2 mb-sm-0">
                            <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center gap-1">
                                <span class="fw-semibold">🛵 Motorcycles</span>
                                <span class="badge bg-secondary">
                                    {{ $area['moto_occupied_count'] }} / {{ $area['moto_total'] }}
                                </span>
                            </div>


                            <div class="btn-group btn-group-sm" role="group" aria-label="Adjust motorcycle count">
                                <button class="btn btn-outline-danger"
                                    wire:click="decrementMoto({{ $area['id'] }})">−</button>
                                <button class="btn btn-outline-success"
                                    wire:click="incrementMoto({{ $area['id'] }})">+</button>
                            </div>
                        </div>
                        @endif

                        @canaccess("edit_parking_area")
                        <div class="ms-sm-auto">
                            <a href="#" class="text-secondary text-decoration-none"
                                wire:click="openEditAreaModalServer({{ $area['id'] ?? $area->id }})">
                                <i class="bi bi-gear-fill fs-5"></i>
                            </a>
                        </div>
                        @endcanaccess
                    </div>


                    {{-- Cars: sensor grid --}}
                    @if(isset($area['car_slots']) && count($area['car_slots']) > 0)
                    <div class="d-flex align-items-center mb-3">
                        <span class="fw-semibold me-3">🚗 Car Slots</span>
                        <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center">
                            <div class="d-flex align-items-center mb-1 mb-sm-0 me-sm-3">
                                <span class="badge bg-success me-1">&nbsp;</span>
                                <small>Available</small>
                            </div>
                            <div class="d-flex align-items-center">
                                <span class="badge bg-danger me-1">&nbsp;</span>
                                <small>Occupied</small>
                            </div>
                        </div>

                    </div>

                    <div class="row row-cols-3 row-cols-sm-3 row-cols-md-4 row-cols-lg-6 g-3">
                        @foreach($area['car_slots'] as $slot)
                        @php
                        $occupied = (bool) $slot['occupied'];
                        $disabled = (bool) $slot['disabled'];
                        $show = $filter === 'all'
                        || ($filter === 'available' && !$occupied)
                        || ($filter === 'occupied' && $occupied);
                        $lastSeen = isset($slot['updated_at']) ? \Carbon\Carbon::parse($slot['updated_at'])->format('M d, Y H:i') : 'N/A';
                        @endphp
                        @if($show)
                        <div class="col">
                            <div class="slot-tile 
                            {{ $disabled ? 'bg-secondary' : ($occupied ? 'bg-danger' : 'bg-success') }} 
                                text-white p-2 rounded d-flex justify-content-center align-items-center"
                                title="Slot {{ $slot['label'] }} — {{ $disabled ? 'Disabled' : ($occupied ? 'Occupied' : 'Available') }}
Last seen: {{ $lastSeen }}"
                                wire:click="openSlot({{ $area['id'] }}, {{ $slot['id'] }})" role="button"
                                style="{{ $disabled ? 'opacity: 0.6; height: 40px;' : 'cursor: pointer; height: 40px;' }}">
                                <span class="slot-label fw-semibold">{{ $slot['label'] }}</span>
                            </div>
                        </div>
                        @endif

                        @endforeach
                    </div>
                    @else
                    <div class="text-muted fst-italic">
                        This area has motorcycle parking only.
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>

<script>
    // Listen for Livewire navigation away from this page
    document.addEventListener('livewire:navigating', () => {
        console.log('🔄 Livewire navigating away - stopping parking map polling...');
        
        // Find and destroy the Alpine parking map component
        const mapContainer = document.getElementById('live-map-container');
        if (mapContainer && mapContainer.__x) {
            try {
                mapContainer.__x.destroy?.();
                console.log('✅ Parking map polling stopped');
            } catch (e) {
                console.warn('⚠️ Error stopping parking map:', e);
            }
        }
        
        // Get the highest interval ID and clear them all as a safety measure
        let id = setInterval(() => {}, 0);
        while (id--) {
            clearInterval(id);
        }
    });
</script>