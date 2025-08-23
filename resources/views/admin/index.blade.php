<!DOCTYPE html>
<html>
<head>
    <title>Analytics</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <h1 style="text-align: center;">Page View Analytics</h1>

    <div style="width: 700px; height: 400px; margin: auto;">
        <canvas id="pageViewsChart"></canvas>
    </div>

    <script>
        const ctx = document.getElementById('pageViewsChart').getContext('2d');

        new Chart(ctx, {
            type: 'bar', // can be 'bar', 'line', etc.
            data: {
                labels: [
                    "2025-08-17",
                    "2025-08-18",
                    "2025-08-19",
                    "2025-08-20",
                    "2025-08-21",
                    "2025-08-22",
                    "2025-08-23",
                    "2025-08-24",
                    "2025-08-25",
                    "2025-08-26"
                ],
                datasets: [{
                    label: 'Page Views',
                    data: [12, 19, 7, 15, 22, 30, 18, 27, 16, 20], // sample values
                    borderColor: 'rgba(75, 192, 192, 1)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>
