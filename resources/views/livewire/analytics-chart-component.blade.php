<div>
    <div class="mb-3">
        <label for="chart-date" class="form-label">Select Date:</label>
        <select id="chart-date" class="form-select" wire:model="selectedDate">
            @foreach($dates as $date)
                <option value="{{ $date }}">{{ $date }}</option>
            @endforeach
        </select>
    </div>

    <div class="h-96 w-full" style="max-width: 1200px; margin: auto;">
        <canvas 
            x-data="{
                chart: null,
                labels: @entangle('labels'),
                data: @entangle('data'),
                init() {
                    const ctx = this.$refs.canvas.getContext('2d');
                    this.chart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: this.labels,
                            datasets: [{
                                label: 'Peak Entries',
                                data: this.data,
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

                    // reactive updates
                    this.$watch('labels', value => { this.chart.data.labels = value; this.chart.update(); });
                    this.$watch('data', value => { this.chart.data.datasets[0].data = value; this.chart.update(); });
                }
            }"
            x-ref="canvas"
        ></canvas>
    </div>
</div>
