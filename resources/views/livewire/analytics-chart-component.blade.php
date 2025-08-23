<div class="w-full" style="max-width: 1200px; margin: auto;">
    <!-- Date Selector -->
    <div class="mb-4">
        <label for="dateSelect" class="block text-sm font-medium text-gray-700 mb-2">
            Select Date:
        </label>
        <select id="dateSelect" wire:model.live="selectedDate" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            @foreach($dates as $date)
                <option value="{{ $date }}">
                    {{ \Carbon\Carbon::parse($date)->format('l, M j, Y') }}
                </option>
            @endforeach
        </select>
    </div>

    <!-- Chart Container -->
    <div class="h-96 w-full" style="height: 500px;">
        <canvas x-data="chartComponent()" x-ref="canvas" wire:ignore></canvas>
    </div>
</div>

<script>
function chartComponent() {
    return {
        chart: null,
        isUpdating: false, // Prevent multiple simultaneous updates
        init() {
            const ctx = this.$refs.canvas.getContext('2d');

            this.chart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: @json($labels),
                    datasets: [{
                        label: 'Peak Entries',
                        data: @json($data),
                        borderColor: 'rgba(75, 192, 192, 1)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: { y: { beginAtZero: true } }
                }
            });

            // Single event listener for custom events
            document.addEventListener('chartDataUpdated', (event) => {
                console.log('Chart data updated:', event.detail);
                this.updateChart(event.detail);
            });
        },

        updateChart(eventData) {
            if (this.isUpdating) {
                console.log('Update skipped - already updating');
                return;
            }
            
            this.isUpdating = true;
            
            try {
                // Extract data properly
                const data = eventData.data || eventData;
                const labels = eventData.labels || [];
                
                console.log('Recreating chart with labels:', labels, 'data:', data);
                
                // Destroy existing chart to prevent corruption
                if (this.chart) {
                    this.chart.destroy();
                    this.chart = null;
                }
                
                // Recreate the chart with new data
                const ctx = this.$refs.canvas.getContext('2d');
                this.chart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Peak Entries',
                            data: Array.isArray(data) ? data : [],
                            borderColor: 'rgba(75, 192, 192, 1)',
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: { y: { beginAtZero: true } }
                    }
                });
                
                console.log('Chart recreated successfully');
            } catch (error) {
                console.error('Error recreating chart:', error);
            } finally {
                this.isUpdating = false;
            }
        }
    }
}
</script>