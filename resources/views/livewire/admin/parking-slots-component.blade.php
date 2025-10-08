{{-- resources\views\livewire\admin\parking-slots-component.blade.php --}}
<div class="container py-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h4 class="mb-0">Parking Slots</h4>
        <div class="btn-group btn-group-sm" role="group" aria-label="Filter slots">
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

                    <div class="d-flex w-100">
                        <!-- Left column: Area Name -->
                        <div class="flex-shrink-0 me-3">
                            <span class="fw-semibold">{{ $area['name'] }}</span>
                        </div>

                        <!-- Right column: car & motorcycle info stacked -->
                        <div class="d-flex flex-column">
                            @if(isset($area['car_slots']) && count($area['car_slots']) > 0)
                            <span class="text-muted">🚗 {{ $area['car_available'] }}/{{ $area['car_total'] }}
                                cars</span>
                            @endif
                            @if ((int) ($area['moto_total'] ?? 0) > 0)
                            <span class="text-muted">🛵 {{ $area['moto_available_count'] }}/{{ $area['moto_total'] }}
                                motorcycles</span>
                            @endif
                        </div>
                    </div>
                </button>
            </h2>

            <div id="collapse-{{ $area['id'] }}" class="accordion-collapse collapse"
                aria-labelledby="heading-{{ $area['id'] }}" wire:ignore.self>
                <div class="accordion-body pt-3 pb-4">
                    {{-- Motorcycles: counter style --}}
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        @if ((int) ($area['moto_total'] ?? 0) > 0)
                        <div class="d-flex align-items-center gap-3">
                            <span class="fw-semibold">🛵 Motorcycles</span>
                            <span class="badge bg-secondary">
                                {{ $area['moto_available_count'] }} / {{ $area['moto_total'] }}
                            </span>
                        </div>
                        <div class="btn-group btn-group-sm ms-2" role="group" aria-label="Adjust motorcycle count">
                            <button class="btn btn-outline-danger"
                                wire:click="decrementMoto({{ $area['id'] }})">−</button>
                            <button class="btn btn-outline-success"
                                wire:click="incrementMoto({{ $area['id'] }})">+</button>

                        </div>
                        @endif
                        <div class="ms-auto">
                            <a href="#" class="text-secondary text-decoration-none"
                                wire:click="openEditAreaModalServer({{ $area['id'] ?? $area->id }})">
                                <i class="bi bi-gear-fill fs-5"></i>
                            </a>
                        </div>
                    </div>

                    {{-- Cars: sensor grid --}}
                    @if(isset($area['car_slots']) && count($area['car_slots']) > 0)
                    <div class="d-flex align-items-center mb-3">
                        <span class="fw-semibold me-3">🚗 Car Slots</span>
                        <span class="badge bg-success me-1">&nbsp;</span>
                        <small class="me-3">Available</small>
                        <span class="badge bg-danger me-1">&nbsp;</span>
                        <small>Occupied</small>
                    </div>

                    <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-6 g-3">
                        @foreach($area['car_slots'] as $slot)
                        @php
                        $occupied = (bool) $slot['occupied'];
                        $show = $filter === 'all'
                        || ($filter === 'available' && !$occupied)
                        || ($filter === 'occupied' && $occupied);
                        @endphp
                        @if($show)
                        <div class="col">
                            <div class="slot-tile {{ $occupied ? 'bg-danger' : 'bg-success' }} text-white p-2 rounded"
                                title="Slot {{ $slot['label'] }} — {{ $occupied ? 'Occupied' : 'Available' }}"
                                wire:click="openSlot({{ $area['id'] }}, {{ $slot['id'] }})" role="button">
                                <span class="slot-label">{{ $slot['label'] }}</span>
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