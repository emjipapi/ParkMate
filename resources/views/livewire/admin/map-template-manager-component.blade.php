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
{{-- Area label (no inline left/top; JS will position it) --}}
<div class="area-label-{{ $selectedMap->id }}"
     data-area="{{ $areaKey }}"
     data-x="{{ $x }}"
     data-y="{{ $y }}"
     data-marker-size="{{ $markerSize }}"
     data-position="{{ $config['label_position'] ?? 'right' }}"
     style="position:absolute;
            background: rgba(0,0,0,{{ $config['label_opacity'] ?? 0.78 }});
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
                        {{-- Label Position --}}
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
{{-- Background Opacity --}}
<div class="col-12">
    <div class="mb-3">
        <label class="form-label small fw-semibold">
            Label Background Opacity
            <span class="text-muted">({{ number_format(($config['label_opacity'] ?? 0.78) * 100, 0) }}%)</span>
        </label>

        <input type="range"
            wire:model.live="areaConfig.{{ $areaKey }}.label_opacity"
            min="0" max="1" step="0.05"
            class="form-range">
        <div class="d-flex justify-content-between">
            <small class="text-muted">Transparent</small>
            <small class="text-muted">Opaque</small>
        </div>
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
    let __mapTimer = null;
    // store { obs: MutationObserver, wrapper: Element } per mapId
    const wrapperObservers = new Map();

    function log(...args) {
        console.log.apply(console, ['[ParkingMap]', ...args]);
    }

    function initAllImages() {
        log('initAllImages() called', new Date().toISOString());
        const imgs = Array.from(document.querySelectorAll('img[id^="map-image-"]'));
        imgs.forEach(img => {
            const m = img.id.match(/^map-image-(.+)$/);
            if (!m) return;
            const mapId = m[1];
            attachHandlers(img, mapId);
            ensureWrapperObserver(mapId, img.parentElement);
        });
    }

    function attachHandlers(img, mapId) {
        // always ensure the wrapper observer is installed for this image's wrapper
        ensureWrapperObserver(mapId, img.parentElement);

        // always try to position (handles re-selected templates)
        if (img.dataset.mapInit === '1') {
            log('attachHandlers: already inited, calling positionMarkers for', mapId);
            positionMarkers(img, mapId);
            return;
        }
        img.dataset.mapInit = '1';
        log('attachHandlers: adding load listener for', mapId);
        img.addEventListener('load', () => {
            log('image load event for', mapId);
            // ensure observer still present (in case wrapper replaced between scheduling)
            ensureWrapperObserver(mapId, img.parentElement);
            positionMarkers(img, mapId);
        });
        if (img.complete) setTimeout(() => {
            log('image.complete initial position for', mapId);
            ensureWrapperObserver(mapId, img.parentElement);
            positionMarkers(img, mapId);
        }, 30);
    }

    function ensureWrapperObserver(mapId, wrapper) {
        if (!wrapper) return;

        const existing = wrapperObservers.get(mapId);
        // If an observer exists but the wrapper element changed, disconnect & recreate
        if (existing) {
            if (existing.wrapper !== wrapper) {
                try { existing.obs.disconnect(); } catch (e) { /* ignore */ }
                wrapperObservers.delete(mapId);
                log('ensureWrapperObserver: previous observer replaced for map', mapId);
            } else {
                // already observing the correct wrapper
                return;
            }
        }

        const obs = new MutationObserver((mutations) => {
            // minor debounce to coalesce many mutations
            clearTimeout(wrapper._parkingDeb);
            wrapper._parkingDeb = setTimeout(() => {
                const img = wrapper.querySelector('#map-image-' + mapId);
                if (img) {
                    log('MutationObserver: changes detected in wrapper for map', mapId, '— repositioning');
                    positionMarkers(img, mapId);
                } else {
                    log('MutationObserver: wrapper changed and image not found for', mapId);
                }
            }, 40);
        });

        // Observe childList+subtree; attributes retained for style/class changes
        obs.observe(wrapper, { childList: true, subtree: true, attributes: true, attributeFilter: ['class', 'style'] });

        wrapperObservers.set(mapId, { obs, wrapper });
        log('MutationObserver attached for map', mapId);
    }

    function readPercent(value) {
        if (value === null || value === undefined) return NaN;
        const n = parseFloat(value);
        return isFinite(n) ? n : NaN;
    }

    function tryLater(fn, img, mapId, attempt = 0) {
        if (attempt > 10) {
            log('tryLater: giving up', fn.name, mapId, attempt);
            return;
        }
        setTimeout(() => fn(img, mapId, attempt + 1), 40 + attempt * 30);
    }

    function positionMarkers(img, mapId, attempt = 0) {
        try {
            if (!img || !document.body.contains(img)) {
                log('positionMarkers: image missing from DOM for', mapId);
                return;
            }
            if (!img.naturalWidth || !img.naturalHeight) {
                log('positionMarkers: image natural size not ready for', mapId, '→ retry');
                return tryLater(positionMarkers, img, mapId, attempt);
            }

            const wrapper = img.parentElement;
            if (!wrapper) return tryLater(positionMarkers, img, mapId, attempt);

            const imgRect = img.getBoundingClientRect();
            const wrapperRect = wrapper.getBoundingClientRect();
            const imgWidth = img.offsetWidth;
            const imgHeight = img.offsetHeight;

            if (!imgWidth || !imgHeight || wrapperRect.width === 0) {
                log('positionMarkers: bad dims → retry', { imgWidth, imgHeight, wrapperWidth: wrapperRect.width });
                return tryLater(positionMarkers, img, mapId, attempt);
            }

            const offsetX = imgRect.left - wrapperRect.left;
            const offsetY = imgRect.top - wrapperRect.top;

            // emit natural dims to Livewire if present
            if (window.Livewire?.emit) Livewire.emit('setPreviewDimensions', img.naturalWidth, img.naturalHeight);

            // position markers (pixel-space)
            const markerEls = Array.from(wrapper.querySelectorAll('.area-marker-' + mapId));
            log('markers found', markerEls.length, 'for', mapId);
            markerEls.forEach(el => {
                const xPercent = readPercent(el.getAttribute('data-x'));
                const yPercent = readPercent(el.getAttribute('data-y'));
                const markerSize = parseFloat(el.getAttribute('data-marker-size')) || parseFloat(el.style.width) || 24;

                const centerX = offsetX + ((isFinite(xPercent) ? xPercent : 50) / 100) * imgWidth;
                const centerY = offsetY + ((isFinite(yPercent) ? yPercent : 50) / 100) * imgHeight;

                el.style.position = 'absolute';
                el.style.left = Math.round(centerX) + 'px';
                el.style.top = Math.round(centerY) + 'px';
                el.style.width = Math.round(markerSize) + 'px';
                el.style.height = Math.round(markerSize) + 'px';
                el.style.transform = 'translate(-50%, -50%)';
                el.classList.add('visible');

                // store computed center for debugging if needed
                el.dataset._centerX = Math.round(centerX);
                el.dataset._centerY = Math.round(centerY);
            });

            // position labels relative to markers; fallback to computing from data-x/y if marker not found
            const labels = Array.from(wrapper.querySelectorAll('.area-label-' + mapId));
            log('labels found', labels.length, 'for', mapId);
            let anyInvalid = false;
            labels.forEach(el => {
                const area = el.getAttribute('data-area');
                const pos = (el.getAttribute('data-position') || 'right').toLowerCase();
                const xPercent = readPercent(el.getAttribute('data-x'));
                const yPercent = readPercent(el.getAttribute('data-y'));
                const markerSize = parseFloat(el.getAttribute('data-marker-size')) || 24;

                // try to find the placed marker first (scoped to wrapper)
                let markerEl = null;
                if (area) {
                    markerEl = wrapper.querySelector('.area-marker-' + mapId + '[data-area="' + area + '"]');
                }

                let centerX, centerY;
                if (markerEl && markerEl.getBoundingClientRect) {
                    const mRect = markerEl.getBoundingClientRect();
                    centerX = mRect.left - wrapperRect.left + mRect.width / 2;
                    centerY = mRect.top - wrapperRect.top + mRect.height / 2;
                    log('label', area, 'found markerEl -> using its bounding rect', { mLeft: mRect.left, mTop: mRect.top });
                } else {
                    // fallback to computing from percent values (same as marker math)
                    if (!isFinite(xPercent) || !isFinite(yPercent)) {
                        anyInvalid = true;
                        return;
                    }
                    centerX = offsetX + (xPercent / 100) * imgWidth;
                    centerY = offsetY + (yPercent / 100) * imgHeight;
                    log('label', area, 'markerEl not found — computed center from percents', { centerX: Math.round(centerX), centerY: Math.round(centerY) });
                }

                const gap = 8;
                const markerRadius = markerSize / 2;
                let leftPx = centerX;
                let topPx = centerY;
                let transform = 'translate(-50%, -50%)';

                switch (pos) {
                    case 'left':
                        leftPx = Math.round(centerX - markerRadius - gap);
                        transform = 'translate(-100%, -50%)';
                        break;
                    case 'top':
                        topPx = Math.round(centerY - markerRadius - gap);
                        transform = 'translate(-50%, -100%)';
                        break;
                    case 'bottom':
                        topPx = Math.round(centerY + markerRadius + gap);
                        transform = 'translateX(-50%)';
                        break;
                    default: // right
                        leftPx = Math.round(centerX + markerRadius + gap);
                        transform = 'translateY(-50%)';
                        break;
                }

                if (!isFinite(leftPx) || !isFinite(topPx)) {
                    anyInvalid = true;
                    return;
                }

                el.style.position = 'absolute';
                el.style.left = leftPx + 'px';
                el.style.top = topPx + 'px';
                el.style.transform = transform;
                el.classList.add('visible');

                // store for debug
                el.dataset._leftPx = leftPx;
                el.dataset._topPx = topPx;
            });

            if (anyInvalid && attempt < 8) {
                log('positionMarkers: some invalid label coords, retrying', mapId, attempt);
                tryLater(positionMarkers, img, mapId, attempt);
            } else {
                log('positionMarkers completed for', mapId);
            }
        } catch (err) {
            console.error('[ParkingMap] positionMarkers error', err);
            if (attempt < 6) tryLater(positionMarkers, img, mapId, attempt);
        }
    }

    // ---- waitForImageThenPosition & click handling (keeps your working logic) ----
    function waitForImageThenPosition(mapId, opts = {}) {
        const intervalMs = opts.interval || 50;
        const timeoutMs  = opts.timeout  || 2000;
        const deadline = Date.now() + timeoutMs;
        let tries = 0;

        return new Promise((resolve) => {
            const id = setInterval(() => {
                tries++;
                const img = document.querySelector('#map-image-' + mapId);
                if (img) {
                    clearInterval(id);
                    log('waitForImageThenPosition: found image after', tries, 'tries for mapId', mapId);
                    // ensure it's attached, observer installed and positioned
                    ensureWrapperObserver(mapId, img.parentElement);
                    if (img.dataset.mapInit !== '1') {
                        attachHandlers(img, mapId);
                    } else {
                        positionMarkers(img, mapId);
                    }
                    resolve(true);
                    return;
                }
                if (Date.now() > deadline) {
                    clearInterval(id);
                    log('waitForImageThenPosition: timed out waiting for image for mapId', mapId);
                    resolve(false);
                }
            }, intervalMs);
        });
    }

    function onDocumentClick(e) {
        // detect elements with wire:click attribute (capture)
        const clicked = e.target.closest('[wire\\:click]');
        if (!clicked) return;
        const attr = clicked.getAttribute('wire:click') || '';
        const m = attr.match(/selectMap\((\d+)\)/);
        if (!m) return;
        const mapId = m[1];
        log('selectMap click detected for mapId', mapId, ' — scheduling waitForImageThenPosition');

        // immediate attempt if img exists already
        const immediateImg = document.querySelector('#map-image-' + mapId);
        if (immediateImg) {
            log('selectMap: image already present — immediate reposition for', mapId);
            ensureWrapperObserver(mapId, immediateImg.parentElement);
            positionMarkers(immediateImg, mapId);
        } else {
            log('selectMap: image not present yet — will poll for it', mapId);
        }

        // poll for image and position (robust)
        waitForImageThenPosition(mapId, { interval: 50, timeout: 2000 })
            .then(found => {
                if (!found) log('selectMap: image still not found after timeout for', mapId);
                else log('selectMap: finished positioning for', mapId);
            });
    }

    // make sure click listener is not duplicated (use capture true to detect wire:click early)
    try { document.removeEventListener('click', onDocumentClick, true); } catch (e) { /* ignore */ }
    document.addEventListener('click', onDocumentClick, true);

    // ---- lifecycle wiring ----
    document.addEventListener('DOMContentLoaded', () => setTimeout(initAllImages, 30));

    document.addEventListener('livewire:update', () => {
        clearTimeout(__mapTimer);
        __mapTimer = setTimeout(initAllImages, 80);
    });
    document.addEventListener('livewire:navigated', () => {
        clearTimeout(__mapTimer);
        __mapTimer = setTimeout(initAllImages, 80);
    });

    // IMPORTANT: run after Livewire processed a message (fires after DOM patch finishes)
    // this ensures initAllImages runs when Livewire replaces the preview wrapper
    document.addEventListener('livewire:message.processed', () => {
        clearTimeout(__mapTimer);
        __mapTimer = setTimeout(() => {
            log('livewire:message.processed -> initAllImages');
            initAllImages();
        }, 30);
    });

    window.addEventListener('resize', () => {
        clearTimeout(__mapTimer);
        __mapTimer = setTimeout(initAllImages, 140);
    });
})();
</script>











