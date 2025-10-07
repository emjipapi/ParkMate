@php
    // Initial data computation - matching Livewire component logic
    $mapData = $map;
    $areaConfig = (array) ($map->area_config ?? []);

    // Collect all referenced parking_area_ids so we can query counts in bulk (avoids N+1)
    $parkingAreaIds = array_values(array_filter(array_map(function ($c) {
        return $c['parking_area_id'] ?? null;
    }, $areaConfig)));

    // Bulk car slot stats: total and occupied (SUM(occupied) returns number of occupied slots)
    $carStats = \App\Models\CarSlot::selectRaw('area_id, COUNT(*) as total, SUM(occupied) as occupied')
        ->whereIn('area_id', $parkingAreaIds ?: [0])
        ->groupBy('area_id')
        ->get()
        ->keyBy('area_id');

    // Bulk motorcycle counts
    $motoRows = \App\Models\MotorcycleCount::whereIn('area_id', $parkingAreaIds ?: [0])
        ->get()
        ->keyBy('area_id');

    // Compute statuses using the pre-fetched data
    $areaStatuses = [];
    foreach ($areaConfig as $areaKey => $cfg) {
        $enabled = !empty($cfg['enabled']);
        $parkingAreaId = $cfg['parking_area_id'] ?? null;

        $totalCarSlots = 0;
        $occupiedCarSlots = 0;
        $availableMotorcycleCount = null;
        $motorcycleTotal = null;

        if ($parkingAreaId) {
            $cs = $carStats[$parkingAreaId] ?? null;
            if ($cs) {
                $totalCarSlots = (int) $cs->total;
                // SUM(occupied) may return null if no rows; cast to int
                $occupiedCarSlots = (int) $cs->occupied;
            }

            $mc = $motoRows[$parkingAreaId] ?? null;
            if ($mc) {
                $availableMotorcycleCount = $mc->available_count !== null ? (int) $mc->available_count : null;
                $motorcycleTotal = $mc->total_available !== null ? (int) $mc->total_available : null;
            }
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
            } elseif ((int) $availableMotorcycleCount > 0) {
                $state = 'moto_only';
            } else {
                $state = 'full';
            }
        } else {
            if ($availableMotorcycleCount !== null && (int) $availableMotorcycleCount > 0) {
                $state = 'available';
            } elseif ($availableMotorcycleCount === 0) {
                $state = 'full';
            } else {
                $state = 'unknown';
            }
        }


        $areaStatuses[$areaKey] = [
            'state' => $state,
            'total' => (int) $totalCarSlots,
            'occupied' => (int) $occupiedCarSlots,
            'available_cars' => (int) $availableCarSlots,
            'motorcycle_available' => $availableMotorcycleCount !== null ? (int) $availableMotorcycleCount : null,
            'motorcycle_total' => $motorcycleTotal !== null ? (int) $motorcycleTotal : null,
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
            html,
            body {
                height: 100%;
                margin: 0;
                background: #fefefe;
            }

            /* viewport covering whole page; centers the image */
            .live-map-viewport {
                position: relative;
                height: 100vh;
                width: 100vw;
                overflow: visible;
                /* Changed from hidden to allow labels to overflow */
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
                display: inline-block;
                /* CRITICAL: inline-block for accurate positioning */
            }

            /* image: as large as possible while preserving aspect ratio */
            .live-map-container img {
                display: block;
                width: auto;
                height: 95vh;
                /* larger than template manager's 600px */
                max-width: 95vw;
                user-select: none;
                -webkit-user-drag: none;
            }

            /* hide until positioned to avoid wrong initial placement */
            .map-marker,
            .map-label {
                visibility: hidden;
            }

            .map-marker.visible,
            .map-label.visible {
                visibility: visible;
            }

            .map-marker {
                position: absolute;
                transform: translate(-50%, -50%);
                border: 2px solid #fff;
                border-radius: 50%;
                z-index: 20;
                display: flex;
                align-items: center;
                justify-content: center;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.45);
                color: #fff;
                font-weight: 600;
                user-select: none;
                pointer-events: none;
                /* change if you want clicks */
            }

            /* map-label: side position and compact sizing */
            .map-label {
                position: absolute;
                z-index: 19;
                /* background: rgba(0, 0, 0, 0.78); */
                color: #fff;
                padding: 6px 8px;
                border-radius: 8px;
                font-size: 13px;
                pointer-events: none;
                white-space: nowrap;
                display: flex;
                align-items: center;
                gap: 10px;
                box-sizing: border-box;
                max-width: 280px;
                /* don't translate here; JS will set transform for vertical centering */
            }

            /* left column: fixed narrow width + truncation */
            .map-label .label-col {
                flex: 0 0 56px;
                /* fixed width */
                max-width: 56px;
                display: flex;
                align-items: center;
                justify-content: center;
                text-align: center;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            /* right column: stacked counts centered vertically */
            .map-label .counts-col {
                display: flex;
                flex-direction: column;
                align-items: flex-start;
                /* keep counts compact; use center if you prefer */
                justify-content: center;
                gap: 3px;
            }

            /* caption + value styles */
            .map-label .caption {
                font-size: 11px;
                color: #d1d5db;
                line-height: 1;
            }

            .map-label .value {
                font-weight: 600;
                line-height: 1;
            }
        </style>

        <div class="live-map-viewport" id="live-map-viewport">
            <div id="live-map-container" class="live-map-container">
                {{-- full-size image (keeps aspect ratio) --}}
                <img src="{{ asset('storage/' . $map->file_path) }}" id="live-map-image" alt="{{ $map->name }}">

<template x-for="(cfg, areaKey) in areaConfig" :key="areaKey">
    <template x-if="cfg.enabled">
        <div>
            <div class="map-marker" :data-area="areaKey" :data-x="cfg.x_percent || 50"
                :data-y="cfg.y_percent || 50" :data-size="cfg.marker_size || 28" :style="{
                width: (cfg.marker_size || 28) + 'px',
                height: (cfg.marker_size || 28) + 'px',
                background: getMarkerColor(areaKey),
                border: '3px solid #fff',
                borderRadius: '50%',
                fontSize: '10px',
                fontWeight: 'bold',
                color: 'white'
              }">
                <span x-show="cfg.show_label_letter !== false" x-text="(cfg.label || 'A').substring(0, 1)"></span>
            </div>


                            <div class="map-label" :data-area="areaKey" :data-position="cfg.label_position || 'right'"
                                :style="{
             background: 'rgba(0, 0, 0, ' + (cfg.label_opacity ?? 0.78) + ')'
         }">
                                <div class="label-col" :title="cfg.label || ''">
                                    <strong x-text="cfg.label || 'A'"></strong>
                                </div>

                                <div class="counts-col">
                                    <div>
                                        <div class="caption">Motorcycles</div>
                                        <div class="value" x-text="areaStatuses[areaKey]
                               ? (
                                   (areaStatuses[areaKey].motorcycle_available !== null && areaStatuses[areaKey].motorcycle_available !== undefined
                                     ? areaStatuses[areaKey].motorcycle_available
                                     : '—')
                                   + ' Available / ' +
                                   (areaStatuses[areaKey].motorcycle_total !== null && areaStatuses[areaKey].motorcycle_total !== undefined
                                     ? areaStatuses[areaKey].motorcycle_total + ' Total'
                                     : '-')
                                 )
                               : '—'"></div>
                                    </div>

                                    <div>
                                        <div class="caption">Car Slots</div>
                                        <div class="value" x-text="areaStatuses[areaKey]
                               ? ((areaStatuses[areaKey].occupied ?? 0) + ' Occupied / ' + (areaStatuses[areaKey].total ?? 0)) + ' Total'
                               : '-'"></div>
                                    </div>
                                </div>
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
                        this.pollInterval = setInterval(() => {
                            this.refreshStatuses();
                        }, 2000);

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

                            if (data.areaConfig) {
                                this.areaConfig = data.areaConfig;

                                // Wait longer for Alpine to finish DOM updates
                                this.$nextTick(() => {
                                    setTimeout(() => {
                                        setTimeout(() => this.repositionElements(), 100);
                                    }, 100);
                                });
                            }
                        } catch (error) {
                            console.error('Error refreshing statuses:', error);
                        }
                    },

                    repositionElements() {
                        const image = document.getElementById('live-map-image');
                        const container = document.getElementById('live-map-container');

                        if (!image || !container || !image.naturalWidth) return;

                        const imgRect = image.getBoundingClientRect();
                        const contRect = container.getBoundingClientRect();
                        const imgWidth = image.offsetWidth;
                        const imgHeight = image.offsetHeight;
                        const offsetLeft = imgRect.left - contRect.left;
                        const offsetTop = imgRect.top - contRect.top;

                        // Position markers
                        const markers = container.querySelectorAll('.map-marker');
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

                        // Position labels
                        const labels = container.querySelectorAll('.map-label');
                        labels.forEach(label => {
                            const areaKey = label.dataset.area;
                            const marker = container.querySelector('.map-marker[data-area="' + areaKey + '"]');
                            if (!marker) return;

                            const mRect = marker.getBoundingClientRect();
                            const mLeft = mRect.left - contRect.left + mRect.width / 2;
                            const mTop = mRect.top - contRect.top + mRect.height / 2;
                            const markerRadius = mRect.width / 2;
                            const gap = 10;

                            const labelPosition = this.areaConfig[areaKey]?.label_position || 'right';
                            let leftPx, topPx, transform;

                            switch (labelPosition) {
                                case 'right':
                                    leftPx = Math.round(mLeft + markerRadius + gap);
                                    topPx = Math.round(mTop);
                                    transform = 'translateY(-50%)';
                                    break;
                                case 'left':
                                    leftPx = Math.round(mLeft - markerRadius - gap);
                                    topPx = Math.round(mTop);
                                    transform = 'translate(-100%, -50%)';
                                    break;
                                case 'top':
                                    leftPx = Math.round(mLeft);
                                    topPx = Math.round(mTop - markerRadius - gap);
                                    transform = 'translate(-50%, -100%)';
                                    break;
                                case 'bottom':
                                    leftPx = Math.round(mLeft);
                                    topPx = Math.round(mTop + markerRadius + gap);
                                    transform = 'translateX(-50%)';
                                    break;
                                default:
                                    leftPx = Math.round(mLeft + markerRadius + gap);
                                    topPx = Math.round(mTop);
                                    transform = 'translateY(-50%)';
                            }

                            label.style.left = leftPx + 'px';
                            label.style.top = topPx + 'px';
                            label.style.transform = transform;
                            label.classList.add('visible');
                        });
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

                        const self = this; // Capture 'this' context

                        if (image.complete) {
                            setTimeout(() => self.repositionElements(), 60);
                        } else {
                            image.addEventListener('load', () => self.repositionElements());
                        }

                        let resizeTimer = null;
                        window.addEventListener('resize', () => {
                            clearTimeout(resizeTimer);
                            resizeTimer = setTimeout(() => self.repositionElements(), 120);
                        });

                        const mo = new MutationObserver(() => {
                            clearTimeout(resizeTimer);
                            resizeTimer = setTimeout(() => self.repositionElements(), 60);
                        });
                        mo.observe(container, { childList: true, subtree: true, attributes: true });

                        let attempts = 0;
                        const bootInterval = setInterval(() => {
                            attempts++;
                            self.repositionElements();
                            if (attempts > 10) clearInterval(bootInterval);
                        }, 300);

                        window.addEventListener('beforeunload', () => {
                            mo.disconnect();
                            clearInterval(bootInterval);
                            clearTimeout(resizeTimer);
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