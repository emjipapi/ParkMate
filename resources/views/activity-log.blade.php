<?php

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>ParkMate - Activity Log</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

  <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
  <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
  <!-- Inter font -->
  <link href="{{ asset('css/fonts.css') }}" rel="stylesheet">
  <!-- Font Awesome -->
  <link href="{{ asset('css/all.min.css') }}" rel="stylesheet">
    <livewire:styles />
    <style>
        :root {
            --sidebar-bg: #182125;
            --sidebar-btn-bg: #182125;
            --sidebar-btn-hover: #6c757d;
            --admin-bg: #2E739F;
            --admin-text: #ffffff;
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
            background-color: var(--sidebar-bg);
        }

        .admin-header {
            background-color: var(--admin-bg);
            color: var(--admin-text);
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
            color: #fff;
            background-color: var(--sidebar-btn-bg);
            border: none;
            padding: 10px;
            border-radius: 8px;
        }

        .sidebar button:hover {
            background-color: rgb(36, 46, 50);
            color: white;
        }

        .sidebar button.active {
            background-color: #2C363B;
            border-left: 4px solid var(--admin-bg);
            padding-left: 14px;
            color: #fff;
        }

        .top-bar {
            background-color: #3481B4;
            color: var(--admin-text);
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
            background-color: #256a99;
            color: white;
            border: none;
            padding: 0 12px;
            font-size: 0.9rem;
            cursor: pointer;
            height: 100%;
        }

        .live-btn:hover {
            background-color: #1b5c7d;
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
            background-color: #3481B4;
            color: white;
            border: none;
            padding: 6px 16px;
            cursor: pointer;
            border-radius: 4px;
            transition: background-color 0.3s ease;
            margin-top: 10px;
        }

        .btn-add-slot:hover {
            background-color: rgb(110, 172, 213);
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
            table-layout: fixed;
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
            <a href="{{ url('/dashboard') }}" style="text-decoration: none;">
                <button class="btn">Dashboard</button>
            </a>
        </div>
        <div class="btn-wrapper">
            <a href="{{ url('/parking-slots') }}" style="text-decoration: none;">
                <button class="btn">Parking Slots</button>
            </a>
        </div>
        <div class="btn-wrapper">
            <a href="{{ url('/violation-tracking') }}"  style="text-decoration: none;">
                <button class="btn">Violation Tracking</button>
            </a>
        </div>
        <div class="btn-wrapper">
            <a href="{{ url('/users') }}" style="text-decoration: none;">
                <button class="btn">Users</button>
            </a>
        </div>
                        <div class="btn-wrapper">
            <a href="{{ url('/sticker-generator') }}"  style="text-decoration: none;">
                <button class="btn">Sticker Generator</button>
            </a>
        </div>
        <div class="btn-wrapper"><button class="btn active">Activity Log</button></div>
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
            <a href="{{ url('/dashboard/live-attendance-mode') }}" style="text-decoration: none;">
                <button class="live-btn">
                    Live Attendance Mode
                </button>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="content">
        <div class="d-flex align-items-baseline justify-content-between mb-3">
            <div class="d-flex align-items-baseline">
                <h3 class="mb-0 me-3">Manage</h3>
                <h6 class="mb-0">Activity Log</h6>
            </div>
            <span class="text-muted">Home > Activity Log</span>
        </div>
        <div class="square-box">
            @livewire('activity-log-component')
        </div>
    </div>
    <!-- Bottom Bar -->
    <div class="bottom-bar">
        <span>Copyright © 2025 - 2025 All rights reserved</span>
        <span>ParkMate</span>
    </div>
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
    flatpickr("#startDate", { dateFormat: "Y-m-d" });
    flatpickr("#endDate", { dateFormat: "Y-m-d" });
</script>
</body>

</html>