<div 
    x-data="{
        labels: {{ Js::from($labels) }},
        data: {{ Js::from($data) }},
        init() {
            const ctx = this.$refs.canvas.getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: this.labels,
                    datasets: [{
                        label: 'Page Views',
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
        }
    }"
    class="h-80"
>
    <canvas x-ref="canvas"></canvas>
</div>
