document.addEventListener('DOMContentLoaded', function () {
    // ==========================================
    // Line Chart: DAILY (Consultations par Jour)
    // ==========================================
    const lineCanvas = document.getElementById('lineChart');
    if (lineCanvas) {
        const ctxLine = lineCanvas.getContext('2d');
        const consultationsData = JSON.parse(lineCanvas.dataset.consultations || '[]');
        const labelsData = JSON.parse(lineCanvas.dataset.labels || '[]');

        let gradient = ctxLine.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(13, 148, 136, 0.2)');
        gradient.addColorStop(1, 'rgba(13, 148, 136, 0)');

        new Chart(ctxLine, {
            type: 'line',
            data: {
                labels: labelsData,
                datasets: [{
                    label: 'Consultations',
                    data: consultationsData,
                    borderColor: '#0d9488',
                    backgroundColor: gradient,
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#0d9488',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        padding: 12,
                        callbacks: {
                            label: function (context) { return context.parsed.y + ' consultation(s)'; }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { borderDash: [5, 5], color: '#f1f5f9' },
                        ticks: { stepSize: 1 }
                    },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // ==========================================
    // Doughnut Chart: COLORFUL (Répartition)
    // ==========================================
    const doughnutCanvas = document.getElementById('doughnutChart');
    if (doughnutCanvas) {
        const ctxDoughnut = doughnutCanvas.getContext('2d');
        const repartitionData = JSON.parse(doughnutCanvas.dataset.repartition || '[]');

        new Chart(ctxDoughnut, {
            type: 'doughnut',
            data: {
                labels: repartitionData.map(item => item.specialite),
                datasets: [{
                    data: repartitionData.map(item => item.total),
                    backgroundColor: [
                        '#3b82f6', // Blue
                        '#10b981', // Green
                        '#f59e0b', // Orange
                        '#ef4444', // Red
                        '#8b5cf6', // Violet
                        '#ec4899', // Pink
                        '#6366f1'  // Indigo
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff',
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            usePointStyle: true,
                            padding: 15,
                            font: { size: 12, family: "'Inter', sans-serif" },
                            boxWidth: 10
                        }
                    }
                }
            }
        });
    }
});
