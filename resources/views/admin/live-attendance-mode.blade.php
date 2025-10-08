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

        .content {
            margin-left: 250px;
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
    </style>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>

<body>

    <!-- Sidebar -->
    <div class="sidebar d-flex flex-column">
        <div class="admin-header">
            <h4>Admin</h4>
        </div>

        <div class="btn-wrapper mt-3">
            <a href="{{ url('/admin-dashboard') }}" style="text-decoration: none;">
                <button class="btn active">Dashboard</button>
            </a>
        </div>
        <div href='/parking-slots' wire:navigate class="btn-wrapper">

            <button class="btn">Parking Slots</button>

        </div>
        <div href='/violation-tracking' wire:navigate class="btn-wrapper">

            <button class="btn">Violation Tracking</button>

        </div>
        <div href='/users' wire:navigate class="btn-wrapper">

            <button class="btn">Users</button>

        </div>
        <div href='/sticker-generator' wire:navigate class="btn-wrapper">

            <button class="btn">Sticker Generator</button>

        </div>
        <div href='/activity-log' wire:navigate class="btn-wrapper">

            <button class="btn">Activity Log</button>

        </div>
        <div class="btn-wrapper"><button class="btn">Settings</button></div>
        <div class="mt-auto p-3">
            <form action="{{ route('admin.logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-danger w-100">Logout</button>
            </form>
        </div>

    </div>

    <div class="top-bar">
        <div id="clock" style="font-size: 1rem;"></div>
        <span style="flex: 1;"></span>


        <div class="live-btn-bar">
            <a href="{{ url('/live-attendance') }}" style="text-decoration: none;">
                <button class="live-btn">
                    Live Attendance Mode
                </button>
            </a>
        </div>
    </div>
    <!-- Main Content -->
    <div class="content">
        <!-- Header stays at the top -->
        <div class="d-flex align-items-baseline justify-content-between mb-3">
            <div class="d-flex align-items-baseline">
                <h3 class="mb-0 me-3">Dashboard</h3>
                <h6 class="mb-0">Live Attendance</h6>
            </div>
            <span class="text-muted">Home > Dashboard > Live Attendance Mode</span>
        </div>

        <!-- Centering only this section -->
        <div class="flex-grow-1 d-flex justify-content-center align-items-center">
            <livewire:admin.live-attendance-component />
        </div>
    </div>


    <!-- Bottom Bar -->
    <div class="bottom-bar">
        <span>Copyright © 2025 - 2025 All rights reserved</span>
        <span>ParkMate</span>
    </div>

    <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
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
    @livewireScripts
</body>

</html>