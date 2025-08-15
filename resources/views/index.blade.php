


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>ParkMate - Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
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

        .edit-bar {
            display: flex;
            align-items: center;
            background-color: white;
            border-radius: 4px;
            overflow: hidden;
            height: 30px;
        }

        .edit-bar input[type="text"] {
            border: none;
            padding: 4px 10px;
            font-size: 0.9rem;
            outline: none;
            width: 180px;
        }

        .edit-btn {
            background-color: #256a99;
            color: white;
            border: none;
            padding: 0 12px;
            font-size: 0.9rem;
            cursor: pointer;
            height: 100%;
        }

        .edit-btn:hover {
            background-color: #1b5c7d;
        }



        .content .cards-container {
            margin-top: auto;
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
            position: fixed;
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
            <a href="{{ url('/users') }}" href="users.php" style="text-decoration: none;">
                <button class="btn">Users</button>
            </a>
        </div>
        <div class="btn-wrapper"><button class="btn">Settings</button></div>
        <div class="mt-auto p-3">
            <form action="logout.php" method="post">
                <button type="submit" class="btn btn-danger w-100">Logout</button>
            </form>
        </div>
    </div>

    <div class="top-bar">
        <span style="flex: 1;"></span>
        <div class="edit-bar">
            <input type="text" placeholder="Awaiting RFID Tag" id="editInput" readonly>
            <button class="edit-btn" title="Edit" onclick="enableEditing()">
                <i class="bi bi-pencil"></i>
            </button>
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

        <livewire:first-component />
        
    </div>
    <!-- Bottom Bar -->
    <div class="bottom-bar">
        <span>Copyright © 2025 - 2025 All rights reserved</span>
        <span>ParkMate</span>
    </div>

    <script>
        function enableEditing() {
            const input = document.getElementById('editInput');
            input.removeAttribute('readonly');
            input.focus();
            input.addEventListener('keydown', function onKeyDown(e) {
                if (e.key === 'Enter') {
                    input.setAttribute('readonly', true);
                    saveInputValue();
                    input.removeEventListener('keydown', onKeyDown);
                }
            });
            input.addEventListener('blur', function onBlur() {
                input.setAttribute('readonly', true);
                saveInputValue();
                input.removeEventListener('blur', onBlur);
            });
        }

        function saveInputValue() {
            const input = document.getElementById('editInput');
            localStorage.setItem('editInputValue', input.value);
        }
        window.addEventListener('DOMContentLoaded', () => {
            const savedValue = localStorage.getItem('editInputValue');
            if (savedValue !== null) {
                document.getElementById('editInput').value = savedValue;
            }
        });
    </script>


</body>

</html>