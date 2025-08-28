<div class="w-full" style="max-width: 1200px; margin: auto;">
    <div class="w-full" style="max-width: 1200px; margin: auto;">
        <!-- Filters Row -->
        <div class="d-flex justify-content-start gap-2 mb-3" wire:loading.class="opacity-50">
            <!-- Date Selector -->
                            <label for="chartType" class="block text-sm font-medium text-gray-700 mb-2">
                    Date:
                </label>
            <input type="date" id="dateSelect" wire:model.live="selectedDate" class="form-control form-control-sm w-auto d-inline 
           focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 
           sm:text-sm disabled:bg-gray-100 disabled:cursor-not-allowed" wire:loading.attr="disabled"
                wire:target="selectedDate,chartType" min="{{ min($dates) }}" max="{{ max($dates) }}"
                onfocus="this.showPicker();" onmousedown="event.preventDefault(); this.showPicker();">

            <!-- Chart Type Selector -->
            <div class="flex-1 min-w-48">
                <label for="chartType" class="block text-sm font-medium text-gray-700 mb-2">
                    Chart Type:
                </label>
                <select id="chartType" wire:model.live="chartType"
                    class="form-control form-control-sm w-auto d-inline focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm disabled:bg-gray-100 disabled:cursor-not-allowed"
                    wire:loading.attr="disabled" wire:target="selectedDate,chartType">
                    <option value="entries">Peak Entry Hours</option>
                    <option value="duration">Average Duration of Stays</option>
                </select>
            </div>
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
                isUpdating: false,
                updateTimeout: null,
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
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    title: {
                                        display: true,
                                        text: 'Number of Entries'
                                    }
                                }
                            }
                        }
                    });

                    // Single event listener for custom events with debouncing
                    document.addEventListener('chartDataUpdated', (event) => {
                        console.log('Chart data updated:', event.detail);

                        // Clear any existing timeout
                        if (this.updateTimeout) {
                            clearTimeout(this.updateTimeout);
                        }

                        // Debounce the update to prevent rapid-fire updates
                        this.updateTimeout = setTimeout(() => {
                            this.updateChart(event.detail);
                        }, 100); // 100ms delay
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

                        // Small delay to ensure canvas is ready
                        setTimeout(() => {
                            try {
                                // Check if canvas context is still valid
                                const ctx = this.$refs.canvas.getContext('2d');
                                if (!ctx) {
                                    console.error('Canvas context is null');
                                    this.isUpdating = false;
                                    return;
                                }

                                const chartType = eventData.chartType || 'entries';
                                const isEntries = chartType === 'entries';

                                this.chart = new Chart(ctx, {
                                    type: 'bar',
                                    data: {
                                        labels: labels,
                                        datasets: [{
                                            label: isEntries ? 'Peak Entries' : 'Avg Duration (minutes)',
                                            data: Array.isArray(data) ? data : [],
                                            borderColor: isEntries ? 'rgba(75, 192, 192, 1)' : 'rgba(255, 99, 132, 1)',
                                            backgroundColor: isEntries ? 'rgba(75, 192, 192, 0.2)' : 'rgba(255, 99, 132, 0.2)',
                                            borderWidth: 2
                                        }]
                                    },
                                    options: {
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        scales: {
                                            y: {
                                                beginAtZero: true,
                                                title: {
                                                    display: true,
                                                    text: isEntries ? 'Number of Entries' : 'Minutes'
                                                }
                                            }
                                        }
                                    }
                                });

                                console.log('Chart recreated successfully');

                                // Add an extra 1-second delay before re-enabling controls
                                setTimeout(() => {
                                    this.isUpdating = false;
                                }, 1000);
                            } catch (error) {
                                console.error('Error recreating chart:', error);
                                this.isUpdating = false;
                            }
                        }, 50); // 50ms delay for canvas to be ready

                    } catch (error) {
                        console.error('Error in updateChart:', error);
                        this.isUpdating = false;
                    }
                }
            }
        }
    </script>