document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.alert .close').forEach((btn) => {
        btn.addEventListener('click', () => btn.closest('.alert').remove());
    });

    document.querySelectorAll('.flash-alert').forEach((alert) => {
        setTimeout(() => {
            alert.style.transition = 'opacity .4s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 400);
        }, 3000);
    });

    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (e) => {
            if (!window.confirm(form.dataset.confirm)) {
                e.preventDefault();
            }
        });
    });

    const chartCanvas = document.getElementById('violationsChart');
    if (chartCanvas) {
        const ctx = chartCanvas.getContext('2d');
        let chartData = { labels: [], data: [] };
        try {
            chartData = JSON.parse(chartCanvas.dataset.chart || '{"labels":[],"data":[]}');
        } catch (e) {
            chartData = { labels: [], data: [] };
        }
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: chartData.labels,
                datasets: [{
                    label: 'Jumlah Pelanggaran',
                    data: chartData.data,
                    backgroundColor: ['#2563EB', '#10B981', '#F59E0B', '#EF4444', '#2563EB', '#10B981']
                }]
            },
            options: {
                responsive: true,
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });
    }
});
