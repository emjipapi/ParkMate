<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>ParkMate - Parking Slots</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap 5 CDN -->
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
    <!-- Inter font -->
    <link href="{{ asset('css/fonts.css') }}" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="{{ asset('css/all.min.css') }}" rel="stylesheet">
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

        .sidebar {
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            background-color: var(--bg4);
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

        .sidebar .btn-wrapper {
            padding: 5px 15px;
        }

        .sidebar button {
            width: 100%;
            text-align: left;
            color: var(--text3);
            background-color: var(--bg4);
            border: none;
            padding: 10px;

        }

        .sidebar button:hover {
            background-color: var(--bg5);
            color: var(--text3);
        }

        .sidebar button.active {
            background-color: #ddf3ff;
            border-left: 4px solid var(--bg1);
            padding-left: 14px;
            color: var(--text2);
            font-weight: 600;
        }

        .top-bar {
            background-color: var(--bg2);
            color: var(--text1);
            display: flex;
            align-items: center;
            padding: 0 20px;
            height: 48px;
            font-weight: 600;
            margin-left: 250px;
            font-size: 1.25rem;
            line-height: 36px;
        }

        .content {
            margin-left: 250px;
            padding: 20px;
            padding-bottom: 60px;
            min-height: calc(100vh - 96px);

            background-color: #EAEEF4;
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
            position: relative;
            bottom: 0;
            left: 250px;
            width: calc(100% - 250px);
            height: 48px;
            background-color: 'white';
            color: 'black';
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
            font-weight: 500;
        }

        .content .cards-container {
            margin-top: auto;

        }

        .card {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }


        .card-footer {
            padding: 0;
            white-space: normal;
            text-align: center;
            background-color: rgba(0, 0, 0, 0.1);
            border: none;
            line-height: 2;
        }

        .card-1 {
            border: 0px;
            border-radius: 0px;
            background-color: #00B8EE;
            color: white;
            height: 200px;
        }

        .card-2 {
            border: 0px;
            border-radius: 0px;
            background-color: #F09113;
            color: white;
            height: 200px;
        }

        .card-3 {
            border: 0px;
            border-radius: 0px;
            background-color: #019C50;
            color: white;
            height: 200px;
        }

        .cards-container {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 1rem;
        }

        /* Place cards exactly */
        .card-5 {
            grid-column: 4;
            grid-row: 1;
            height: 200px;
            border: 0;
            border-radius: 0;
            background: #6c63ff;
            color: #fff;
        }

        .card-1 {
            grid-column: 1;
            grid-row: 2;
            height: 200px;
        }

        .card-2 {
            grid-column: 2;
            grid-row: 2;
            height: 200px;
        }

        .card-3 {
            grid-column: 3;
            grid-row: 2;
            height: 200px;
        }

        .card-4 {
            grid-column: 4;
            grid-row: 2;
            border: 0;
            border-radius: 0;
            background: #fff;
            color: #000;
            height: 450px;
            overflow-y: auto;
            padding: 1rem;
        }

        /* Keep cards as flex boxes for internal layout (OK with grid) */
        .cards-container .card {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        /* Remove leftover flex styles for cards inside grid */
        .cards-container .card {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .card-4 h5 {
            font-size: 1.2rem;
            margin-bottom: 1rem;
            border-bottom: 1px solid #ddd;
            padding-bottom: 0.5rem;
        }

        .recent-activity-item {
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
        }

        .recent-activity-item:last-child {
            border-bottom: none;
        }

        .recent-activity-status {
            font-weight: bold;
            color: #007BFF;
            /* can adjust color per action type if needed */
        }

        .mobile-menu-btn {
            display: none;
            /* hidden on desktop */
            background: none;
            border: none;
            font-size: 1.5rem;
            color: white;
            margin-right: 10px;
        }

        .square-box {
            background-color: white;
            width: 100%;
            min-height: 100px;
            padding: 20px;
            margin-top: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        @media (max-width: 768px) {
            .cards-container {
                display: flex;
                flex-direction: column;
                gap: 1rem;
                /* spacing between cards */
            }

            .cards-container .card {
                width: 100%;
            }

            .mobile-menu-btn {
                display: inline-block;
            }

            .sidebar {
                transform: translateX(-100%);
                /* hidden */
                z-index: 1000;
                transition: transform 0.3s ease-in-out;

            }

            .sidebar .mt-auto {
                margin-top: 0 !important;
            }

            .sidebar.open {
                transform: translateX(0);
                /* slide in */
            }

            .content,
            .top-bar,
            .bottom-bar {
                margin-left: 0;
                /* full width on mobile */
            }

            .bottom-bar {
                left: 0;
                width: 100%;
                position: relative;
                /* or fixed if you want it always at bottom */
                padding: 0 10px;
                /* optional: less padding for mobile */
                flex-direction: column;
                /* optional: stack items vertically if needed */
                gap: 5px;
            }

            .d-flex.align-items-baseline h3 {
                font-size: 1.2rem;
                /* smaller heading on mobile */
            }

            .d-flex.align-items-baseline h6 {
                font-size: 0.9rem;
                /* smaller subheading */
            }

            .d-flex.align-items-baseline span,
            .d-flex.align-items-baseline .text-white {
                font-size: 0.8rem;
                /* smaller breadcrumb text */
            }
                                  .content {
            padding: 10px;
            padding-bottom: 20px;
        }
        }
    </style>
</head>

<body>

    <!-- Sidebar -->
    <div class="sidebar d-flex flex-column">

        <div class="admin-header">
            <h4>User</h4>
        </div>
        <button class="mobile-menu-btn" onclick="openSidebar()">☰</button>

        <div class="btn-wrapper mt-3" href='/user-dashboard' wire:navigate><button class="btn">Dashboard</button></div>
        <div class="btn-wrapper">
            <button class="btn active">Parking Slots</button>
        </div>
        <div class="btn-wrapper" href='/user-violation-tracking' wire:navigate>
            <button class="btn">Violation Tracking</button>
        </div>
        <div class="btn-wrapper" href='/user-settings' wire:navigate>
            <button class="btn">Settings</button>
        </div>

        <div class="mt-auto p-3">
            <form action="{{ route('user.logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-danger w-100">Logout</button>
            </form>
        </div>
    </div>

    <div class="top-bar">
        <button class="mobile-menu-btn" onclick="openSidebar()">☰</button>
        <div id="clock" style="font-size: 1rem;"></div>
        <span style="flex: 1;"></span>
    </div>
    <!-- Main Content -->
    <div class="content">
        <div class="d-flex align-items-baseline justify-content-between mb-3">
            <div class="d-flex align-items-baseline">
                <h3 class="mb-0 me-3">View</h3>
                <h6 class="mb-0">All Slots</h6>
            </div>
            <span class="text-muted">Home > Parking Slots</span>
        </div>
        <div class="square-box">
            <livewire:user.parking-slots-component />
        </div>
    </div>
    <!-- Bottom Bar -->
    <div class="bottom-bar">
        <span>Copyright © 2025 - 2025 All rights reserved</span>
        <span>ParkMate</span>
    </div>
    @livewireScripts
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
    <script>
        function openSidebar() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('open');
        }
        function closeSidebar() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('open');
        }
    </script>

</body>

</html>