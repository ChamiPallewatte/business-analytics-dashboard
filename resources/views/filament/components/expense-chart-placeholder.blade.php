@php
    $expenses = \App\Models\Expense::query()
        ->selectRaw('type, SUM(amount) as total')
        ->whereMonth('expense_date', now()->month)
        ->whereYear('expense_date', now()->year)
        ->groupBy('type')
        ->pluck('total', 'type')
        ->toArray();

    $labels = array_keys($expenses);
    $data = array_values($expenses);
    
    if (empty($expenses)) {
        $labels = ['Marketing', 'Software', 'Hosting', 'Freelancers', 'Others'];
        $data = [0, 0, 0, 0, 0];
    }
@endphp

<div x-data="{
    chart: null,
    init() {
        this.$nextTick(() => {
            const canvas = this.$refs.canvas;
            if (!canvas) return;
            
            if (typeof Chart === 'undefined') {
                const script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
                script.onload = () => this.renderChart(canvas);
                document.head.appendChild(script);
            } else {
                this.renderChart(canvas);
            }
        });
    },
    renderChart(canvas) {
        const ctx = canvas.getContext('2d');
        if (this.chart) {
            this.chart.destroy();
        }
        this.chart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: {{ json_encode($labels) }},
                datasets: [{
                    data: {{ json_encode($data) }},
                    backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#6b7280'],
                    borderWidth: 1,
                    borderColor: document.documentElement.classList.contains('dark') ? '#1e293b' : '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                cutout: '70%'
            }
        });
    }
}" class="flex flex-col items-center justify-center p-2">
    <div style="position: relative; height: 160px; width: 160px;">
        <canvas x-ref="canvas"></canvas>
    </div>
    
    <div class="mt-4 w-full text-xs space-y-1 max-h-32 overflow-y-auto">
        @foreach($labels as $index => $label)
            <div class="flex justify-between items-center py-0.5 border-b border-gray-100 dark:border-gray-800 last:border-0">
                <div class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full inline-block" style="background-color: {{ ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#6b7280'][$index % 6] }}"></span>
                    <span class="text-gray-600 dark:text-gray-400 font-medium">{{ $label }}</span>
                </div>
                <span class="text-gray-900 dark:text-gray-100 font-semibold">{{ \App\Models\Setting::getCurrency() }} {{ number_format($data[$index], 2) }}</span>
            </div>
        @endforeach
    </div>
</div>
