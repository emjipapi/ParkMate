{{-- resources/views/parking-map/live.blade.php --}}
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Live Parking Map</title>

    {{-- Livewire styles (required) --}}
    @livewireStyles

    <style>
        :root{
            --bg:#0b0b0b;
        }
        html,body{
            height:100%;
            margin:0;
            font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
            background: var(--bg);
            color: #fff;
        }

        /* full-viewport container that centers the Livewire component */
        .live-map-viewport {
            min-height: 100vh;
            display:flex;
            align-items:center;
            justify-content:center;
            padding: 18px;
            box-sizing: border-box;
        }

        /* make sure Livewire component can expand to full available space if it wants */
        .livewire-component-wrapper {
            width: 100%;
            height: 100%;
            max-width: 100vw;
            max-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
                .mobile-warning {
    display: none;
}
@media (max-width: 768px) {
    .desktop-content {
        display: none !important;
    }
    .mobile-warning {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 100vh;
        background: #f8f9fa;
        color: #333;
        text-align: center;
    }
}
    </style>
</head>
<body>
            <!-- Show this message only on mobile -->
    <div class="mobile-warning">
        <div class="text-center p-5">
            <h3>This page is only available on desktop</h3>
            <p>Please use a larger screen to view this page.</p>
        </div>
    </div>

    <!-- Main page content -->
    <div class="desktop-content">
    <main class="live-map-viewport" role="main">
        <div class="livewire-component-wrapper" id="livewire-map-root">
            {{-- your Livewire component — exact tag you asked for --}}
           
                <livewire:admin.parking-map-live-view-component :map-id="$map->id" />
           
        </div>
    </main>
</div>
    {{-- Livewire scripts (required) --}}
    @livewireScripts
</body>
</html>
