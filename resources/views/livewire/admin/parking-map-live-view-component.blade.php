<div wire:poll.5000ms="refreshStatuses">
    @if(!$map)
        <div style="height:100vh; display:flex; align-items:center; justify-content:center;">
            <div class="text-center text-muted">
                <h3>No map found</h3>
                <p>Select or upload a parking map first in the manager.</p>
            </div>
        </div>
        @return
    @endif

    <style>
        html, body { height: 100%; margin: 0; }
        .live-map-viewport {
            height: 100vh;
            width: 100vw;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #0b0b0b;
        }
        .live-map-container {
            position: relative;
            max-width: 95vw;
            max-height: 95vh;
            border-radius: 8px;
            overflow: visible;
            box-shadow: 0 8px 40px rgba(0,0,0,0.6);
        }
        .live-map-container img {
            display:block;
            max-width: 100%;
            max-height: 100%;
            width: auto;
            height: auto;
        }
        .map-marker {
            position: absolute;
            transform: translate(-50%,-50%);
            border: 3px solid #fff;
            border-radius: 50%;
            z-index: 50;
            display:flex;
            align-items:center;
            justify-content:center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.45);
            cursor: default;
            color: #fff;
            font-weight: 600;
            user-select: none;
        }
        .map-label {
            position: absolute;
            transform: translateX(-50%);
            z-index: 49;
            background: rgba(0,0,0,0.7);
            color: #fff;
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 13px;
            white-space: nowrap;
        }
        /* colors (used inline as well) */
    </style>

    <div class="live-map-viewport" id="live-map-viewport">
        <div id="live-map-container" class="live-map-container" style="background: #111;">
            {{-- image --}}
            <img src="{{ asset('storage/' . $map->file_path) }}" id="live-map-image" alt="{{ $map->name }}">

            {{-- overlay markers --}}
            @foreach($areaConfig as $areaKey => $cfg)
                @php
                    $enabled = !empty($cfg['enabled']);
                    $x = $cfg['x_percent'] ?? 50;
                    $y = $cfg['y_percent'] ?? 50;
                    $markerSize = $cfg['marker_size'] ?? 28;
                    $label = $cfg['label'] ?? 'A';
                    $showLetter = $cfg['show_label_letter'] ?? true;
                    $status = $areaStatuses[$areaKey]['state'] ?? 'unknown';
                    $color = match($status) {
                        'full' => '#dc2626',
                        'available' => '#16a34a',
                        'moto_only' => '#f59e0b',
                        'disabled' => '#94a3b8',
                        default => '#6b7280',
                    };
                @endphp

                @if(!empty($cfg['enabled']))
                    <div
                        class="map-marker"
                        data-area="{{ $areaKey }}"
                        data-x="{{ $x }}"
                        data-y="{{ $y }}"
                        data-size="{{ $markerSize }}"
                        data-map-id="{{ $map->id }}"
                        style="left: {{ $x }}%; top: {{ $y }}%; width: {{ $markerSize }}px; height: {{ $markerSize }}px; background: {{ $color }};"
                        title="{{ $label }}">
                        @if($showLetter)
                            {{ substr($label, 0, 1) }}
                        @endif
                    </div>

                    <div
                        class="map-label"
                        data-area="{{ $areaKey }}"
                        style="left: {{ $x }}%; top: calc({{ $y }}% + {{ $markerSize/2 + 8 }}px);">
                        {{ $label }}
                        @if(isset($areaStatuses[$areaKey]))
                            <div style="font-size:11px; opacity:0.9; margin-top:3px;">
                                @php $st = $areaStatuses[$areaKey]; @endphp
                                @if($st['total'] > 0)
                                    {{ $st['occupied'] }}/{{ $st['total'] }} occupied
                                @else
                                    Cars: - 
                                @endif
                                @if($st['motorcycle_available'] !== null)
                                    • M: {{ $st['motorcycle_available'] }}
                                @endif
                            </div>
                        @endif
                    </div>
                @endif
            @endforeach
        </div>
    </div>

    {{-- Positioning script: uses percent positions and recalculates on load, resize and Livewire updates --}}
    <script>
        (function () {
            const image = document.getElementById('live-map-image');
            const container = document.getElementById('live-map-container');

            function positionOverlays() {
                // container may have different size than img natural
                const imgRect = image.getBoundingClientRect();
                const contRect = container.getBoundingClientRect();
                const imgWidth = image.offsetWidth;
                const imgHeight = image.offsetHeight;

                // left/top offset of image relative to container
                const offsetLeft = imgRect.left - contRect.left;
                const offsetTop = imgRect.top - contRect.top;

                // markers
                document.querySelectorAll('#live-map-container .map-marker').forEach(marker => {
                    const xPercent = parseFloat(marker.dataset.x) || 50;
                    const yPercent = parseFloat(marker.dataset.y) || 50;
                    const size = parseFloat(marker.dataset.size) || 28;

                    const x = offsetLeft + (xPercent/100) * imgWidth;
                    const y = offsetTop + (yPercent/100) * imgHeight;

                    marker.style.left = Math.round(x) + 'px';
                    marker.style.top = Math.round(y) + 'px';
                    marker.style.width = Math.round(size) + 'px';
                    marker.style.height = Math.round(size) + 'px';
                    // keep circular center transform
                    marker.style.transform = 'translate(-50%, -50%)';
                });

                document.querySelectorAll('#live-map-container .map-label').forEach(label => {
                    const xPercent = parseFloat(label.dataset.x) || parseFloat(label.style.left) || 50;
                    const yPercent = parseFloat(label.dataset.y) || null;
                    // label's data-x/data-y may not be set; use inline style left/top when created
                    const leftStyle = label.style.left;
                    const topStyle = label.style.top;

                    // compute using corresponding marker if available
                    const area = label.dataset.area;
                    const marker = document.querySelector('#live-map-container .map-marker[data-area="' + area + '"]');
                    if (marker) {
                        const rect = marker.getBoundingClientRect();
                        const mLeft = rect.left - contRect.left + rect.width/2;
                        const mTop = rect.top - contRect.top + rect.height/2;
                        // place label slightly below marker
                        label.style.left = Math.round(mLeft) + 'px';
                        label.style.top = Math.round(mTop + (rect.height/2) + 8) + 'px';
                        label.style.transform = 'translateX(-50%)';
                    }
                });
            }

            // run after image loads
            if (image.complete) {
                setTimeout(positionOverlays, 40);
            } else {
                image.addEventListener('load', () => setTimeout(positionOverlays, 40));
            }

            // recalc on resize
            window.addEventListener('resize', () => {
                clearTimeout(window.__mapPosTimer);
                window.__mapPosTimer = setTimeout(positionOverlays, 120);
            });

            // recalc after Livewire updates
            document.addEventListener('livewire:update', () => {
                clearTimeout(window.__mapPosTimer);
                window.__mapPosTimer = setTimeout(positionOverlays, 80);
            });

            // also reposition periodically (in case layout shifts) for the first few seconds
            let bootCount = 0;
            const bootInt = setInterval(() => {
                positionOverlays();
                bootCount++;
                if (bootCount > 6) clearInterval(bootInt);
            }, 400);
        })();
    </script>
</div>
