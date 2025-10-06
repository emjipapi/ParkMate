@php
    // Initial data computation - matching Livewire component logic
    $mapData = $map;
    $areaConfig = (array) ($map->area_config ?? []);
    
    // Compute initial statuses
    $areaStatuses = [];
    foreach ($areaConfig as $areaKey => $cfg) {
        $enabled = !empty($cfg['enabled']);
        $parkingAreaId = $cfg['parking_area_id'] ?? null;
        
        $totalCarSlots = 0;
        $occupiedCarSlots = 0;
        $availableMotorcycleCount = null;
        
        if ($parkingAreaId) {
            $totalCarSlots = \App\Models\CarSlot::where('area_id', $parkingAreaId)->count();
            $occupiedCarSlots = \App\Models\CarSlot::where('area_id', $parkingAreaId)->where('occupied', 1)->count();
            
            $mc = \App\Models\MotorcycleCount::where('area_id', $parkingAreaId)->first();
            $availableMotorcycleCount = $mc?->available_count ?? null;
        }
        
        $availableCarSlots = max(0, $totalCarSlots - $occupiedCarSlots);
        
        // Determine state - exact same logic as Livewire
        $state = 'unknown';
        if (!$enabled) {
            $state = 'disabled';
        } elseif ($totalCarSlots > 0 && $availableCarSlots > 0) {
            $state = 'available';
        } elseif ($totalCarSlots > 0 && $availableCarSlots === 0) {
            if ($availableMotorcycleCount === null) {
                $state = 'full';
            } elseif ((int)$availableMotorcycleCount > 0) {
                $state = 'moto_only';
            } else {
                $state = 'full';
            }
        } else {
            if ($availableMotorcycleCount !== null && (int)$availableMotorcycleCount > 0) {
                $state = 'available';
            } elseif ($availableMotorcycleCount === 0) {
                $state = 'full';
            } else {
                $state = 'unknown';
            }
        }
        
        $areaStatuses[$areaKey] = [
            'state' => $state,
            'total' => (int)$totalCarSlots,
            'occupied' => (int)$occupiedCarSlots,
            'available_cars' => (int)$availableCarSlots,
            'motorcycle_available' => $availableMotorcycleCount !== null ? (int)$availableMotorcycleCount : null,
        ];
    }
@endphp

<div x-data="parkingMap(@js($mapData), @js($areaConfig), @js($areaStatuses))" x-init="init()">
    @if(!$map)
        <div style="height:100vh; display:flex; align-items:center; justify-content:center;">
            <div class="text-center text-muted">
                <h3>No map found</h3>
                <p>Select or upload a parking map first in the manager.</p>
            </div>
        </div>
    @else
        <style>
            html, body {
                height: 100%;
                margin: 0;
                background: #fefefe;
            }

            /* viewport covering whole page; centers the image */
            .live-map-viewport {
                position: relative;
                height: 100vh;
                width: 100vw;
                overflow: hidden;
                background: #ffffff;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 0;
                box-sizing: border-box;
            }

            /* container is the positioning reference for overlays */
            .live-map-container {
                position: relative;
                display: inline-block; /* CRITICAL: inline-block for accurate positioning */
            }

            /* image: as large as possible while preserving aspect ratio */
            .live-map-container img {
                display: block;
                width: auto;
                height: 95vh; /* larger than template manager's 600px */
                max-width: 95vw;
                user-select: none;
                -webkit-user-drag: none;
            }

            /* hide until positioned to avoid wrong initial placement */
            .map-marker, .map-label {
                visibility: hidden;
            }
            .map-marker.visible, .map-label.visible {
                visibility: visible;
            }

            .map-marker {
                position: absolute;
                transform: translate(-50%, -50%);
                border: 2px solid #fff;
                border-radius: 50%;
                z-index: 20;
                display:flex;
                align-items:center;
                justify-content:center;
                box-shadow: 0 4px 12px rgba(0,0,0,0.45);
                color: #fff;
                font-weight: 600;
                user-select: none;
                pointer-events: none; /* change if you want clicks */
            }

            .map-label {
                position: absolute;
                transform: translateX(-50%);
                z-index: 19;
                background: rgba(0,0,0,0.7);
                color: #fff;
                padding: 6px 10px;
                border-radius: 6px;
                font-size: 13px;
                white-space: nowrap;
                pointer-events: none;
            }
        </style>

        <div class="live-map-viewport" id="live-map-viewport">
            <div id="live-map-container" class="live-map-container">
                {{-- full-size image (keeps aspect ratio) --}}
                <img src="{{ asset('storage/' . $map->file_path) }}" id="live-map-image" alt="{{ $map->name }}">

                {{-- overlay markers --}}
                <template x-for="(cfg, areaKey) in areaConfig" :key="areaKey">
                    <template x-if="cfg.enabled">
                        <div>
                            <div
                                class="map-marker"
                                :data-area="areaKey"
                                :data-x="cfg.x_percent || 50"
                                :data-y="cfg.y_percent || 50"
                                :data-size="cfg.marker_size || 28"
                                :style="{
                                    width: (cfg.marker_size || 28) + 'px',
                                    height: (cfg.marker_size || 28) + 'px',
                                    background: getMarkerColor(areaKey),
                                    fontSize: '10px'
                                }">
                                <span x-show="cfg.show_label_letter !== false" x-text="(cfg.label || 'A').substring(0, 1)"></span>
                            </div>

                            <div class="map-label" :data-area="areaKey">
                                <strong x-text="cfg.label || 'A'"></strong>
                                <template x-if="areaStatuses[areaKey]">
                                    <div style="font-size:11px; opacity:0.9; margin-top:6px;">
                                        <template x-if="areaStatuses[areaKey].total > 0">
                                            <span x-text="areaStatuses[areaKey].occupied + '/' + areaStatuses[areaKey].total + ' occupied'"></span>
                                        </template>
                                        <template x-if="areaStatuses[areaKey].total === 0">
                                            <span>Cars: -</span>
                                        </template>
                                        <template x-if="areaStatuses[areaKey].motorcycle_available !== null">
                                            <span x-text="' • M: ' + areaStatuses[areaKey].motorcycle_available"></span>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </template>
            </div>
        </div>

        <script>
        function parkingMap(mapData, initialAreaConfig, initialAreaStatuses) {
            return {
                map: mapData,
                areaConfig: initialAreaConfig,
                areaStatuses: initialAreaStatuses,
                pollInterval: null,
                positioningSetup: false,

                init() {
                    // Start polling every 1000ms (matching wire:poll.1000ms)
                    this.pollInterval = setInterval(() => {
                        this.refreshStatuses();
                    }, 2000);

                    // Setup positioning
                    this.$nextTick(() => {
                        setTimeout(() => this.setupPositioning(), 100);
                    });
                },

                async refreshStatuses() {
                    if (!this.map || !this.map.id) return;
                    
                    try {
                        const response = await fetch(`/api/parking-map/${this.map.id}/statuses`);
                        const data = await response.json();
                        this.areaStatuses = data.areaStatuses;
                    } catch (error) {
                        console.error('Error refreshing statuses:', error);
                    }
                },

                getMarkerColor(areaKey) {
                    const status = this.areaStatuses[areaKey]?.state || 'unknown';
                    const colorMap = {
                        'full': '#dc2626',
                        'available': '#16a34a',
                        'moto_only': '#f59e0b',
                        'disabled': '#94a3b8'
                    };
                    return colorMap[status] || '#6b7280';
                },

                setupPositioning() {
                    if (this.positioningSetup) return;
                    this.positioningSetup = true;

                    const image = document.getElementById('live-map-image');
                    const container = document.getElementById('live-map-container');

                    if (!image || !container) {
                        setTimeout(() => this.setupPositioning(), 100);
                        return;
                    }

                    // single positioning function
                    function positionOverlays() {
                        if (!image.naturalWidth || !image.naturalHeight) {
                            setTimeout(positionOverlays, 60);
                            return;
                        }

                        const imgRect = image.getBoundingClientRect();
                        const contRect = container.getBoundingClientRect();
                        const imgWidth = image.offsetWidth;
                        const imgHeight = image.offsetHeight;
                        const offsetLeft = imgRect.left - contRect.left;
                        const offsetTop = imgRect.top - contRect.top;

                        const markers = container.querySelectorAll('.map-marker');
                        if (!markers.length) return;

                        markers.forEach((marker) => {
                            const xPercent = parseFloat(marker.dataset.x) || 50;
                            const yPercent = parseFloat(marker.dataset.y) || 50;
                            const size = parseFloat(marker.dataset.size) || 28;

                            const x = offsetLeft + (xPercent / 100) * imgWidth;
                            const y = offsetTop + (yPercent / 100) * imgHeight;

                            marker.style.left = Math.round(x) + 'px';
                            marker.style.top = Math.round(y) + 'px';
                            marker.style.width = Math.round(size) + 'px';
                            marker.style.height = Math.round(size) + 'px';
                            marker.style.transform = 'translate(-50%, -50%)';

                            marker.classList.add('visible');
                        });

                        const labels = container.querySelectorAll('.map-label');
                        labels.forEach(label => {
                            const area = label.dataset.area;
                            const marker = container.querySelector('.map-marker[data-area="' + area + '"]');
                            if (!marker) return;

                            const mRect = marker.getBoundingClientRect();
                            const cont = container.getBoundingClientRect();
                            const mLeft = mRect.left - cont.left + mRect.width / 2;
                            const mTop = mRect.top - cont.top + mRect.height / 2;

                            label.style.left = Math.round(mLeft) + 'px';
                            label.style.top = Math.round(mTop + (mRect.height / 2) + 8) + 'px';
                            label.style.transform = 'translateX(-50%)';

                            label.classList.add('visible');
                        });

                        setTimeout(() => {
                            container.querySelectorAll('.map-marker, .map-label').forEach(el => el.classList.add('visible'));
                        }, 120);
                    }

                    let posTimer = null;
                    function schedulePosition() {
                        clearTimeout(posTimer);
                        posTimer = setTimeout(positionOverlays, 60);
                    }

                    if (image.complete) {
                        schedulePosition();
                    } else {
                        image.addEventListener('load', schedulePosition);
                    }

                    window.addEventListener('resize', () => {
                        clearTimeout(posTimer);
                        posTimer = setTimeout(positionOverlays, 120);
                    });

                    const mo = new MutationObserver((mutations) => {
                        let should = false;
                        for (const m of mutations) {
                            if (m.addedNodes.length || m.removedNodes.length || m.type === 'attributes') { 
                                should = true; 
                                break; 
                            }
                        }
                        if (should) schedulePosition();
                    });
                    mo.observe(container, { childList: true, subtree: true, attributes: true });

                    let attempts = 0;
                    const bootInterval = setInterval(() => {
                        attempts++;
                        positionOverlays();
                        if (attempts > 10) clearInterval(bootInterval);
                    }, 300);

                    window.addEventListener('beforeunload', () => { 
                        mo.disconnect(); 
                        clearInterval(bootInterval); 
                        clearTimeout(posTimer); 
                    });
                },

                destroy() {
                    if (this.pollInterval) {
                        clearInterval(this.pollInterval);
                    }
                }
            };
        }
        </script>
    @endif
</div>