<?php

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>ParkMate - Violation Tracking</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap 5 CDN -->
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <!-- Inter font -->
    <link href="{{ asset('css/fonts.css') }}" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="{{ asset('css/all.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('bootstrap-icons.css') }}">
    <livewire:styles />
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


        .btn-add-slot {
            display: inline-block;
            width: auto;
            background-color: var(--bg1);
            color: white;
            border: none;
            padding: 6px 16px;
            cursor: pointer;
            border-radius: 4px;
            transition: background-color 0.3s ease;
            margin-top: 10px;
        }

        .btn-add-slot:hover {
            background-color: var(--bg3);
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

        .custom-table {
            width: 100%;
            border-collapse: collapse;
            text-align: center;
            min-width: 1000px;
            /* adjust based on how many columns you want visible */
            table-layout: auto;
            /* allow natural column widths */
        }


        .custom-table th,
        .custom-table td {
            border: none;
            padding: 10px;
            background-color: white;
            vertical-align: middle;
            text-align: center;
        }

        .custom-table thead th {
            font-weight: bold;
        }

        .custom-table tbody tr:nth-child(even) {
            background-color: #f8f8f8;
        }

        .btn-action {
            background: none;
            border: none;
            font-size: 0.9rem;
            font-weight: 500;
            margin: 0 4px;
            padding: 4px 8px;
            cursor: pointer;
        }

        .btn-edit {
            color: #D48112;
        }

        .btn-edit:hover {
            text-decoration: none;
            color: rgb(230, 162, 74);
        }

        .btn-delete {
            color: #B04141;
        }

        .btn-delete:hover {
            text-decoration: none;
            color: rgb(201, 84, 84);
        }

        .btn-occupy {
            color: #1565c0;
        }

        .btn-occupy:hover {
            text-decoration: none;
            color: #0d47a1;
        }

        .status-label {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 4px;
            color: white;
            font-weight: 600;
            font-size: 0.9em;
            user-select: none;
        }

        .status-available {
            background-color: #28a745;
        }

        .status-occupied {
            background-color: #fd7e14;
        }

        .btn-action.btn-occupy:disabled {
            color: #999;
            cursor: not-allowed;
            opacity: 0.6;
            background: none;
            border: none;
            pointer-events: none;
        }

        .status-label.status-occupied.tooltip-container {
            position: relative;
            cursor: pointer;
            display: inline-block;
        }

        .status-label.status-occupied .tooltip-text {
            visibility: hidden;
            width: max-content;
            max-width: 200px;
            background-color: rgba(0, 0, 0, 0.75);
            color: #fff;
            text-align: center;
            border-radius: 6px;
            padding: 5px 8px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%);
            opacity: 0;
            transition: opacity 0.3s;
            pointer-events: none;
        }

        .status-label.status-occupied.tooltip-container:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
            pointer-events: auto;
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



        .tabs-container {
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
            /* Hide scrollbars */
            scrollbar-width: none;
            /* Firefox */
            -ms-overflow-style: none;
            /* IE/Edge */
        }

        .tabs-container::-webkit-scrollbar {
            display: none;
            /* Chrome, Safari, Opera */
        }

        .nav-tabs {
            min-width: max-content;
            flex-wrap: nowrap;
            display: flex;
        }

        .nav-tabs .nav-link {
            cursor: pointer;
        }

        /* Hide table scrollbars too */
        .table-responsive {
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
        }

        @media (max-width: 1200px) {
            body {
                overflow-x: auto;
                /* allow scrollable content */
            }

            .table-responsive {
                overflow-x: auto;
                overflow-y: hidden;
                -webkit-overflow-scrolling: touch;
            }

            .custom-table {
                min-width: 1000px;
                /* force horizontal scroll if screen is too narrow */
            }
        }


        @media (max-width: 768px) {

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

            .table-responsive {
                overflow-x: auto;
                overflow-y: hidden;
                /* ⬅️ removes the tiny vertical scroll */
                -webkit-overflow-scrolling: touch;
            }

            .tabs-container {
                margin: 0 -20px;
                /* extend to screen edges */
                padding: 0 20px;
                /* add padding back inside */
                touch-action: pan-x;
                /* enable horizontal touch scrolling */
            }

            .nav-link {
                font-size: 0.9rem;
                /* slightly smaller text on mobile */
                padding: 8px 16px !important;
                /* adjust padding */
            }

            .table-responsive {
                touch-action: pan-x pan-y;
                /* enable horizontal touch scrolling for table */
            }
  .btn-group.w-100 .btn {
    font-size: 1rem; /* bigger text */
    padding: 10px 18px; /* more space to tap */
  }

  /* Ensure the button group takes full width of its container */
  .btn-group.w-100 {
    width: 100% !important;
  }

  /* Prevent tiny split dropdown buttons */
  .btn-group .dropdown-toggle-split {
    padding: 10px 14px;
    min-width: 48px; /* ensures the toggle isn't too small */
  }
    td .btn-group .btn {
    padding: 0.75rem 1.5rem; /* taller + wider tap area */
    font-size: 0.95rem;
  }
          .content {
            padding: 10px;
            padding-bottom: 20px;
        }
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
        <button class="mobile-menu-btn" onclick="openSidebar()">☰</button>
        <div class="btn-wrapper mt-3">
            <a href="{{ url('/admin-dashboard') }}" style="text-decoration: none;">
                <button class="btn">Dashboard</button>
            </a>
        </div>
        <div href='/parking-slots' wire:navigate class="btn-wrapper">

            <button class="btn">Parking Slots</button>

        </div>

        <div class="btn-wrapper"><button class="btn active">Violation Tracking</button></div>
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
        <button class="mobile-menu-btn" onclick="openSidebar()">☰</button>
        <div id="clock" style="font-size: 1rem;"></div>
        <span style="flex: 1;"></span>
    </div>

    <!-- Main Content -->
    <div class="content">
        <div class="d-flex align-items-baseline justify-content-between mb-3">
            <div class="d-flex align-items-baseline">
                <h3 class="mb-0 me-3">Ano ilalagay ko</h3>
                <h6 class="mb-0">otid</h6>
            </div>
            <span class="text-muted">Home > Violation Tracking</span>
        </div>
        <div class="d-flex gap-2 ms-3">
            <div href='/create-report' onclick="window.location='{{ url('/create-report') }}'">
                <button type="button" class="btn-add-slot btn btn-primary">
                    Create Report
                </button>
            </div>
        </div>

        <div class="square-box">
            <livewire:admin.violation-admin-component />

        </div>

    </div>
    <!-- Bottom Bar -->
    <div class="bottom-bar">
        <span>Copyright © 2025 - 2025 All rights reserved</span>
        <span>ParkMate</span>
    </div>
    <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>

    {{-- <script src="{{ asset('js/alpine.min.js') }}"></script> --}}
    <livewire:scripts />

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