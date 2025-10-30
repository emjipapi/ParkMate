<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>ParkMate - Live Attendance Mode</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap 5 CDN -->
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <!-- Inter font -->
    <link href="{{ asset('css/fonts.css') }}" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="{{ asset('css/all.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('bootstrap-icons.css') }}">
    <style>
        :root {
            --bg1: #56ca8b;
            --bg2: #3bc480;
            --bg3: #38b174;
            --bg4: #ffffffff;
            --bg5: #def2ff;
            --text1: #ffffff;
            --text2: #4b6fc0;
            --text3: #8b8d8e;
        }

        body {
            margin: 0;
            overflow-x: hidden;
            font-family: 'Inter', sans-serif;
        }



        .admin-header {
            background-color: var(--bg1);
            color: var(--text1);
            text-align: center;
        }

        .admin-header h4 {
            margin: 0;
            padding: 0;
            line-height: 48px;
            font-size: 1.25rem;
        }



        .top-bar {
            background-color: var(--bg2);
            color: var(--text1);
            display: flex;
            align-items: center;
            padding: 0 20px;
            height: 48px;
            font-weight: 600;
            font-size: 1.25rem;
            line-height: 36px;
        }



        .content {

            padding: 20px;
            padding-bottom: 60px;
            min-height: calc(100vh - 96px);
            display: flex;
            flex-direction: column;
            background-color: #EAEEF4;
        }


        .content .cards-container {
            margin-top: auto;
        }

        h1 {
            font-weight: 600;
        }

        .content h3 {
            color: black;
        }

        .content h6 {
            color: black;
        }

        .bottom-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            width: 100%;
            z-index: 1000;

            height: 48px;
            background-color: white;
            color: black;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
            font-weight: 500;
        }
                .live-btn-bar {
            display: flex;
            align-items: center;
            background-color: white;
            border-radius: 4px;
            overflow: hidden;
            height: 30px;
        }

        .live-btn-bar input[type="text"] {
            border: none;
            padding: 4px 10px;
            font-size: 0.9rem;
            outline: none;
            width: 180px;
        }

        .live-btn {
            background-color: var(--bg3);
            color: white;
            border: none;
            padding: 0 12px;
            font-size: 0.9rem;
            cursor: pointer;
            height: 100%;
        }

        .live-btn:hover {
            background-color: var(--bg1);
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
        <div class="top-bar">
            {{-- <div id="admin-name" style="font-size: 1rem; font-weight: bold;">{{ Auth::guard('admin')->user()->lastname }}, {{ Auth::guard('admin')->user()->firstname }}</div> --}}
            <div id="clock" style="font-size: 1rem;"></div>
            <span style="flex: 1;"></span>
        </div>

        <div class="content">
            <div class="d-flex align-items-baseline justify-content-between mb-3">
                <div class="d-flex align-items-baseline">
                    <h3 class="mb-0 me-3">Live Attendance</h3>
                </div>
                <div>
                    <a href="{{ url('/admin-dashboard/live-attendance-mode') }}" style="text-decoration: none;">
                        <button type="button" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-fullscreen-exit"></i>
                            Exit Full Screen
                        </button>
                    </a>
                </div>
            </div>

            <div class="flex-grow-1 d-flex justify-content-center align-items-center">
                <livewire:admin.live-attendance-fullscreen-component />
            </div>
        </div>

        <div class="bottom-bar">
            <span>Copyright © 2025 - 2025 All rights reserved</span>
            <span>ParkMate</span>
        </div>
    </div>
        <script>
        function updateClock() {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');

        document.getElementById('clock').textContent =
            `${hours}:${minutes}:${seconds}`;
    }

    setInterval(updateClock, 1000);
    updateClock(); // run once immediately
    </script>
</body>


</html>