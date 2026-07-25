// V2.9.19 U-1: 个人中心内容统计面板 JS
function initStats(trendData) {
    if (!trendData || !trendData.length) return;
    var ctx = document.getElementById('trendChart');
    if (!ctx) return;

    var labels = [];
    var data = [];
    trendData.forEach(function(item) {
        labels.push(item.date.substring(5));
        data.push(item.views);
    });

    new Chart(ctx.getContext('2d'), {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: '阅读量',
                data: data,
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13,110,253,0.08)',
                tension: 0.3,
                fill: true,
                pointRadius: 2,
                pointHoverRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { precision: 0 }
                }
            }
        }
    });
}
