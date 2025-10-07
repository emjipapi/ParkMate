{{-- resources/views/livewire/admin/parking-map-manager-component.blade.php --}}

<div class="bg-white rounded-lg shadow-md p-6">
    {{-- Flash Messages --}}
    @if (session()->has('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
        {{ session('success') }}
    </div>
    @endif

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        {{-- Map List --}}
        <div class="xl:col-span-1 space-y-4">
            <!-- Upload New Map -->
            <div class="mb-4 mt-4">
                <div class="text-black">
                    <h5 class="mb-0">Upload New Parking Map</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3 mt-3">
                        <label class="form-label">Map Name</label>
                        <input type="text" wire:model="mapName" class="form-control"
                            placeholder="Enter map name (e.g., Main Parking Lot)">
                        @error('mapName')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Map Image</label>
                        <input type="file" wire:model="mapFile" accept="image/*" class="form-control">
                        @error('mapFile')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-grid">
                        <button wire:click="uploadNewMap" class="btn-add-slot btn btn-primary"
                            wire:loading.attr="disabled">
                            <span wire:loading.remove>Upload Map</span>
                            <span wire:loading>Uploading...</span>
                        </button>
                    </div>
                </div>
            </div>

            <h3 class="text-lg font-medium text-gray-800">Existing Maps</h3>

            {{-- Map List --}}
            <div class="d-flex flex-wrap gap-3 mb-5">
                @forelse($maps as $map)
                <div class="card text-center shadow-sm {{ $selectedMapId == $map->id ? 'border-primary' : '' }}"
                    style="width: 180px; cursor: pointer; transition: all 0.2s;"
                    wire:click="selectMap({{ $map->id }})">

                    <div class="card-body p-2">
                        <h6 class="card-title mb-1 text-truncate">{{ $map->name }}</h6>
                        <small class="text-muted d-block mb-1">{{ $map->width }}x{{ $map->height }}px</small>
                        <span class="badge {{ $selectedMapId == $map->id ? 'bg-success' : 'bg-secondary' }}">
                            {{ $selectedMapId == $map->id ? 'Active' : 'Inactive' }}
                        </span>
                    </div>

                    <div class="card-footer bg-transparent border-0 p-2">
                        <button type="button" class="btn btn-sm btn-outline-danger w-100"
                            onclick="event.stopPropagation(); if(confirm('Are you sure you want to delete this map? This cannot be undone.')) { @this.deleteMap({{ $map->id }}) }"
                            wire:loading.attr="disabled" aria-label="Delete map {{ $map->name }}">
                            <i class="bi bi-trash-fill"></i>
                        </button>
                    </div>
                </div>
                @empty
                <div class="text-center py-4 text-muted w-100">
                    <p class="mb-0">No parking maps found</p>
                    <small>Upload your first map above</small>
                </div>
                @endforelse
            </div>
        </div>

        {{-- Map Editor --}}
        <div class="xl:col-span-2">
            @if($selectedMap)
            <div class="space-y-4">
                {{-- Map Info & Actions --}}
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="text-lg font-medium text-gray-800">{{ $selectedMap->name }}</h3>
                        <p class="text-sm text-gray-600">{{ $selectedMap->width }} x
                            {{ $selectedMap->height }}px ({{ $selectedMap->aspect_ratio }} ratio)
                        </p>
                    </div>
                </div>

                {{-- Map Preview --}}
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="flex justify-between items-center mb-3">
                        <h4 class="font-medium text-gray-700">Parking Map Preview</h4>
                    </div>

                    <div class="flex justify-center">
                        <div id="map-wrapper-{{ $selectedMap->id }}"
                            style="position: relative; display: inline-block; border: 2px solid #d1d5db; border-radius: .375rem; overflow: visible; padding: 0;">
                            <img src="{{ $selectedMap->file_url }}" id="map-image-{{ $selectedMap->id }}"
                                alt="{{ $selectedMap->name }}"
                                style="display:block; width:100%; height:auto; max-height:600px;">

                            {{-- Render parking area markers --}}
                            @foreach($areaConfig as $areaKey => $config)
                            @if(!empty($config['enabled']))
                            @php
                            $x = $config['x_percent'] ?? 50;
                            $y = $config['y_percent'] ?? 50;
                            $markerSize = $config['marker_size'] ?? 24;
                            $markerColor = '#6b7280'; // Neutral gray color
                            $label = $config['label'] ?? 'Area';
                            $showLabelLetter = $config['show_label_letter'] ?? true;
                            
                            // Get parking area details if linked
                            $parkingArea = null;
                            if (!empty($config['parking_area_id'])) {
                                $parkingArea = $availableParkingAreas->firstWhere('id', $config['parking_area_id']);
                            }
                            @endphp

                            {{-- Area marker (red dot) --}}
                            <div class="area-marker-{{ $selectedMap->id }}" 
                                data-area="{{ $areaKey }}"
                                data-x="{{ $x }}"
                                data-y="{{ $y }}"
                                style="position:absolute;
                                    left: {{ $x }}%;
                                    top: {{ $y }}%;
                                    transform: translate(-50%,-50%);
                                    width: {{ $markerSize }}px;
                                    height: {{ $markerSize }}px;
                                    background: {{ $markerColor }};
                                    border: 3px solid #fff;
                                    border-radius: 50%;
                                    z-index: 20;
                                    cursor: pointer;
                                    box-shadow: 0 2px 8px rgba(0,0,0,0.3);
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    font-size: 10px;
                                    font-weight: bold;
                                    color: white;">
                                @if($showLabelLetter)
                                {{ substr($label, 0, 1) }}
                                @endif
                            </div>

                            {{-- Area label --}}
                            <div class="area-label-{{ $selectedMap->id }}" 
                                data-area="{{ $areaKey }}"
                                data-x="{{ $x }}"
                                data-y="{{ $y }}"
                                data-marker-size="{{ $markerSize }}"
                                style="position:absolute;
                                    left: {{ $x }}%;
                                    top: calc({{ $y }}% + {{ $markerSize/2 + 5 }}px);
                                    transform: translateX(-50%);
                                    background: rgba(0,0,0,0.75);
                                    color: white;
                                    padding: 4px 8px;
                                    border-radius: 4px;
                                    font-size: 11px;
                                    white-space: nowrap;
                                    z-index: 19;">
                                {{ $label }}
                                @if($parkingArea)
                                <br><small>({{ $parkingArea->name }})</small>
                                @endif
                            </div>
                            @endif
                            @endforeach
                        </div>
                    </div>
                    
                    @if($isEditing)
                    <div class="mt-3 text-xs text-gray-600 text-center space-y-1">
                        <p><strong>Positioning Guide:</strong> Circular markers show parking area positions</p>
                        <p>Click markers to highlight the corresponding configuration below</p>
                    </div>
                    @endif
                </div>

                {{-- Area Configuration --}}
                <div class="bg-white border rounded-lg p-4">
                    <div class="flex justify-between items-center mb-3">
                        <h4 class="font-medium text-gray-700">Parking Areas Configuration</h4>
                        <div class="flex gap-2">
                            <button wire:click="saveAreaPositions" class="btn-add-slot btn btn-primary">
                                Save Positions
                            </button>
                        </div>
                    </div>

{{-- Parking Area Cards --}}
<div class="row g-4">
    @foreach($areaConfig as $areaKey => $config)
    <div class="col-12 col-lg-6">
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="form-check">
                        <input type="checkbox" 
                               class="form-check-input" 
                               wire:model="areaConfig.{{ $areaKey }}.enabled" 
                               wire:click="saveAreaPositions" 
                               id="enable-area-{{ $areaKey }}">
                        <label class="form-check-label fw-semibold" for="enable-area-{{ $areaKey }}">
                            Enable this area
                        </label>
                    </div>
                    <button wire:click="removeParkingArea('{{ $areaKey }}')" 
                        class="btn btn-sm btn-outline-danger"
                        onclick="return confirm('Remove this parking area?')">
                        <i class="bi bi-trash"></i> Remove
                    </button>
                </div>

                <div class="{{ empty($config['enabled']) ? 'opacity-50 pointer-events-none' : '' }}">
                    <div class="row gy-3">
                        {{-- Label & Parking Area Selection --}}
                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Area Label</label>
                                <input type="text"
                                    wire:model.live="areaConfig.{{ $areaKey }}.label"
                                    class="form-control form-control-sm"
                                    placeholder="Area name">
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Link to Parking Area</label>
                                <select wire:model.live="areaConfig.{{ $areaKey }}.parking_area_id"
                                    class="form-select form-select-sm">
                                    <option value="">-- Select Area --</option>
                                    @foreach($availableParkingAreas as $parkingArea)
                                    <option value="{{ $parkingArea->id }}">{{ $parkingArea->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Position --}}
                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">X Position (%)</label>
                                <input type="number"
                                    wire:model.live="areaConfig.{{ $areaKey }}.x_percent" 
                                    min="0" max="100" step="0.1"
                                    class="form-control form-control-sm">
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Y Position (%)</label>
                                <input type="number"
                                    wire:model.live="areaConfig.{{ $areaKey }}.y_percent" 
                                    min="0" max="100" step="0.1"
                                    class="form-control form-control-sm">
                            </div>
                        </div>

                        {{-- Marker Settings --}}
                        <div class="col-12">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Marker Size (px)</label>
                                <input type="number"
                                    wire:model.live="areaConfig.{{ $areaKey }}.marker_size" 
                                    min="16" max="60" step="2"
                                    class="form-control form-control-sm">
                            </div>
                        </div>
                        
                        <div class="col-12">
    <div class="mb-3">
        <label class="form-label small fw-semibold">Label Position</label>
        <select wire:model.live="areaConfig.{{ $areaKey }}.label_position" 
                class="form-select form-select-sm">
            <option value="right">Right</option>
            <option value="left">Left</option>
            <option value="top">Top</option>
            <option value="bottom">Bottom</option>
        </select>
    </div>
</div>

                        {{-- Show Letter Inside Marker --}}
                        <div class="col-12">
                            <div class="form-check">
                                <input type="checkbox" 
                                    class="form-check-input" 
                                    wire:model.live="areaConfig.{{ $areaKey }}.show_label_letter" 
                                    id="show-letter-{{ $areaKey }}">
                                <label class="form-check-label small" for="show-letter-{{ $areaKey }}">
                                    Show first letter inside marker
                                </label>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

                                                <button wire:click="addParkingArea" class="btn-add-slot btn btn-primary">
                                <i class="bi bi-plus-circle"></i> Add Area
                            </button>
                </div>
            </div>
            @else
            <div class="text-center py-16 text-gray-500">
                <p class="mt-2">Select a parking map to configure</p>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Positioning script --}}
<script>
    (function () {
        if (!window.__parkingMapInit) window.__parkingMapInit = { inited: true };
        let __mapTimer = null;

        function initAllImages() {
            document.querySelectorAll('img[id^="map-image-"]').forEach(img => {
                const idMatch = img.id.match(/^map-image-(.+)$/);
                if (!idMatch) return;
                const mapId = idMatch[1];
                attachHandlers(img, mapId);
            });
        }

        function attachHandlers(img, mapId) {
            if (img.dataset.mapInit === '1') {
                positionMarkers(img, mapId);
                return;
            }
            img.dataset.mapInit = '1';

            img.addEventListener('load', () => positionMarkers(img, mapId));
            if (img.complete) setTimeout(() => positionMarkers(img, mapId), 30);
        }

        function positionMarkers(img, mapId) {
            if (!img || !document.body.contains(img)) return;

            if (!img.naturalWidth || !img.naturalHeight) {
                setTimeout(() => positionMarkers(img, mapId), 60);
                return;
            }

            const container = img.parentElement;
            if (!container) return;

            const imgRect = img.getBoundingClientRect();
            const containerRect = container.getBoundingClientRect();
            const imgWidth = img.offsetWidth;
            const imgHeight = img.offsetHeight;

            const offsetX = imgRect.left - containerRect.left;
            const offsetY = imgRect.top - containerRect.top;

            // Emit dimensions to Livewire
            if (window.Livewire && typeof Livewire.emit === 'function') {
                Livewire.emit('setPreviewDimensions', img.naturalWidth, img.naturalHeight);
            }

            // Position area markers
            document.querySelectorAll('.area-marker-' + mapId).forEach(el => {
                const xPercent = parseFloat(el.dataset.x) || 50;
                const yPercent = parseFloat(el.dataset.y) || 50;

                const x = offsetX + (xPercent / 100) * imgWidth;
                const y = offsetY + (yPercent / 100) * imgHeight;

                el.style.left = Math.round(x) + 'px';
                el.style.top = Math.round(y) + 'px';
            });

            // Position area labels
            document.querySelectorAll('.area-label-' + mapId).forEach(el => {
                const xPercent = parseFloat(el.dataset.x) || 50;
                const yPercent = parseFloat(el.dataset.y) || 50;
                const markerSize = parseFloat(el.dataset.markerSize) || 40;

                const x = offsetX + (xPercent / 100) * imgWidth;
                const y = offsetY + (yPercent / 100) * imgHeight + (markerSize/2 + 5);

                el.style.left = Math.round(x) + 'px';
                el.style.top = Math.round(y) + 'px';
            });
        }

        // Initial run
        document.addEventListener('DOMContentLoaded', () => setTimeout(initAllImages, 30));

        // Re-run after Livewire patches
        document.addEventListener('livewire:update', () => {
            clearTimeout(__mapTimer);
            __mapTimer = setTimeout(initAllImages, 80);
        });
        document.addEventListener('livewire:navigated', () => {
            clearTimeout(__mapTimer);
            __mapTimer = setTimeout(initAllImages, 80);
        });

        // Window resize
        window.addEventListener('resize', () => {
            clearTimeout(__mapTimer);
            __mapTimer = setTimeout(initAllImages, 140);
        });
    })();
</script>