{{-- resources\views\livewire\admin\analytics-chart-component.blade.php --}}
<div class="w-full" style="max-width: 1200px; margin: auto;">
    <div class="w-full" style="max-width: 1200px; margin: auto;">
        <!-- Filters Row -->
        <div class="d-flex justify-content-start gap-2 mb-3" wire:loading.class="opacity-50">
            <!-- Date Selector -->
            <label for="dateSelect" class="block text-sm font-medium text-gray-700 mb-2">
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
                    <option value="logins">User Logins</option>
                    <option value="admin_logins">Admin Logins</option>
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
                currentChartType: @json($chartType),
                
                init() {
                    const ctx = this.$refs.canvas.getContext('2d');
                    const initialLabels = @json($labels);
                    const initialData = @json($data);

                    this.chart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: initialLabels,
                            datasets: [{
                                label: this.getDatasetLabel(this.currentChartType),
                                data: initialData,
                                borderColor: this.getBorderColor(this.currentChartType),
                                backgroundColor: this.getBackgroundColor(this.currentChartType),
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
                                        text: this.getYAxisLabel(this.currentChartType)
                                    }
                                }
                            }
                        }
                    });

                    // Listen for chart updates
                    document.addEventListener('chartDataUpdated', (event) => {
                        console.log('Chart data updated:', event.detail);

                        if (this.updateTimeout) {
                            clearTimeout(this.updateTimeout);
                        }

                        this.updateTimeout = setTimeout(() => {
                            this.updateChart(event.detail);
                        }, 100);
                    });
                },

                updateChart(eventData) {
                    if (this.isUpdating) {
                        console.log('Update skipped - already updating');
                        return;
                    }

                    this.isUpdating = true;

                    try {
                        const data = eventData.data || [];
                        const labels = eventData.labels || [];
                        const chartType = eventData.chartType || 'entries';
                        this.currentChartType = chartType;

                        console.log('Updating chart with:', { labels, data, chartType });

                        // Destroy and recreate chart (your working approach)
                        if (this.chart) {
                            this.chart.destroy();
                            this.chart = null;
                        }

                        setTimeout(() => {
                            try {
                                const ctx = this.$refs.canvas.getContext('2d');
                                if (!ctx) {
                                    console.error('Canvas context is null');
                                    this.isUpdating = false;
                                    return;
                                }

                                this.chart = new Chart(ctx, {
                                    type: 'bar',
                                    data: {
                                        labels: labels,
                                        datasets: [{
                                            label: this.getDatasetLabel(chartType),
                                            data: Array.isArray(data) ? data : [],
                                            borderColor: this.getBorderColor(chartType),
                                            backgroundColor: this.getBackgroundColor(chartType),
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
                                                    text: this.getYAxisLabel(chartType)
                                                }
                                            }
                                        }
                                    }
                                });

                                console.log('Chart recreated successfully');

                                setTimeout(() => {
                                    this.isUpdating = false;
                                }, 1000);
                            } catch (error) {
                                console.error('Error recreating chart:', error);
                                this.isUpdating = false;
                            }
                        }, 50);

                    } catch (error) {
                        console.error('Error in updateChart:', error);
                        this.isUpdating = false;
                    }
                },

                getDatasetLabel(chartType) {
                    const labels = {
                        'entries': 'Peak Entries',
                        'duration': 'Avg Duration (minutes)',
                        'logins': 'User Logins',
                        'admin_logins': 'Admin Logins'
                    };
                    return labels[chartType] || 'Data';
                },

                getYAxisLabel(chartType) {
                    const labels = {
                        'entries': 'Number of Entries',
                        'duration': 'Minutes',
                        'logins': 'Number of Logins',
                        'admin_logins': 'Number of Logins'
                    };
                    return labels[chartType] || 'Value';
                },

                getBorderColor(chartType) {
                    const colors = {
                        'entries': 'rgba(75, 192, 192, 1)',
                        'duration': 'rgba(255, 99, 132, 1)',
                        'logins': 'rgba(54, 162, 235, 1)',
                        'admin_logins': 'rgba(255, 206, 86, 1)'
                    };
                    return colors[chartType] || 'rgba(0, 0, 0, 1)';
                },

                getBackgroundColor(chartType) {
                    const colors = {
                        'entries': 'rgba(75, 192, 192, 0.2)',
                        'duration': 'rgba(255, 99, 132, 0.2)',
                        'logins': 'rgba(54, 162, 235, 0.2)',
                        'admin_logins': 'rgba(255, 206, 86, 0.2)'
                    };
                    return colors[chartType] || 'rgba(0, 0, 0, 0.2)';
                }
            }
        }
    </script>
</div>