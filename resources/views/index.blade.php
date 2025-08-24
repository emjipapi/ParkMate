<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>ParkMate - Dashboard</title>
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
            display: flex;
            flex-direction: column;
            background-image: url('{{ asset('images/image1.png') }}');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            box-shadow: inset 0 60px 30px -20px rgba(0, 0, 0, 0.5);
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

        h1 {
            font-weight: 600;
        }

        .content h3 {
            color: white;
        }

        .content h6 {
            color: white;
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
.cards-container {
    display: flex;
    flex-wrap: wrap;         /* allow stacking */
    gap: 1rem;
    align-items: flex-end;   /* bottom align */
    justify-content: space-between;
}

.cards-container .card {
    flex: 1 1 calc(25% - 0.75rem); /* 4 cards per row minus gaps */
    min-width: 200px;              /* optional: prevent too narrow */
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
.card-4 {
    border: 0;
    border-radius: 0;
    background-color: white;
    color: black;
    height: 650px;
    flex: 0 0 100px; /* fixed width */
    overflow-y: auto; /* allow scrolling if content exceeds height */
    padding: 1rem;
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
    color: #007BFF; /* can adjust color per action type if needed */
}

        @media (max-width: 768px) {
    .cards-container {
        flex-direction: column;  /* stack vertically */
        align-items: stretch;    /* make them full-width */
    }

    .cards-container .card {
        width: 100%; /* take full width of container */
    }
}
    </style>
</head>

<body>

    <!-- Sidebar -->
    <div class="sidebar d-flex flex-column">
        <div class="admin-header">
            <h4>Admin</h4>
        </div>

        <div class="btn-wrapper mt-3"><button class="btn active">Dashboard</button></div>
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
            <a href="{{ url('/users') }}"  style="text-decoration: none;">
                <button class="btn">Users</button>
            </a>
        </div>
                <div class="btn-wrapper">
            <a href="{{ url('/sticker-generator') }}"  style="text-decoration: none;">
                <button class="btn">Sticker Generator</button>
            </a>
        </div>
        <div class="btn-wrapper">
            <a href="{{ url('/activity-log') }}" href="users.php" style="text-decoration: none;">
                <button class="btn">Activity Log</button>
            </a>
        </div>
                        <div class="btn-wrapper">
            <a href=""  style="text-decoration: none;">
                <button class="btn">Settings</button>
            </a>
        </div>
        
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
                <h3 class="mb-0 me-3">Dashboard</h3>
                <h6 class="mb-0">Control Panel</h6>
            </div>
            <span class="text-white">Home > Dashboard</span>
        </div>

        <livewire:cards-component />
        
    </div>
    <!-- Bottom Bar -->
    <div class="bottom-bar">
        <span>Copyright © 2025 - 2025 All rights reserved</span>
        <span>ParkMate</span>
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