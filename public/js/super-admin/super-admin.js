document.addEventListener('DOMContentLoaded', function () {
    const analytics = window.dashboardAnalytics;
    if (!analytics) {
        return;
    }

    if (!window.Chart) {
        console.error('Chart.js did not load; system analytics charts are unavailable.');
        return;
    }

    Chart.defaults.font.family = 'Inter, ui-sans-serif, system-ui, sans-serif';
    Chart.defaults.color = '#64748b';
    const animation = window.matchMedia('(prefers-reduced-motion: reduce)').matches
        ? false
        : { duration: 550 };

    const registrationCanvas = document.getElementById('departmentStudentChart');
    if (registrationCanvas) {
        new Chart(registrationCanvas, {
            type: 'line',
            data: {
                labels: analytics.registrationLabels,
                datasets: analytics.registrationDatasets.map(function (dataset) {
                    return {
                        ...dataset,
                        borderWidth: 2.5,
                        pointRadius: 3,
                        pointHoverRadius: 6,
                        pointBorderWidth: 2,
                        pointBackgroundColor: '#fff',
                    };
                }),
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, padding: 16 } },
                    tooltip: {
                        callbacks: {
                            title: function (items) {
                                return items.length ? items[0].label : '';
                            },
                            label: function (context) {
                                return `${context.dataset.label}: ${context.parsed.y} new students`;
                            },
                        },
                    },
                },
                scales: {
                    x: { grid: { display: false } },
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0 },
                        grid: { color: '#edf0f4' },
                    },
                },
            },
        });
    }

    const performanceCanvas = document.getElementById('departmentPerformanceChart');
    if (performanceCanvas) {
        const targetPlugin = {
            id: 'passingTarget',
            afterDraw: function (chart) {
                const scoreScale = chart.scales.x;
                if (!scoreScale) {
                    return;
                }

                const targetX = scoreScale.getPixelForValue(75);
                const { ctx, chartArea } = chart;
                ctx.save();
                ctx.beginPath();
                ctx.setLineDash([6, 5]);
                ctx.strokeStyle = '#d69e12';
                ctx.lineWidth = 2;
                ctx.moveTo(targetX, chartArea.top);
                ctx.lineTo(targetX, chartArea.bottom);
                ctx.stroke();
                ctx.restore();
            },
        };

        new Chart(performanceCanvas, {
            type: 'bar',
            plugins: [targetPlugin],
            data: {
                labels: analytics.performanceLabels,
                datasets: analytics.performanceDatasets,
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                animation,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                const score = context.parsed.x;
                                return `Average score: ${score === null ? 'No data' : `${score}%`}`;
                            },
                            afterBody: function (items) {
                                const department = analytics.departments[items[0]?.dataIndex];
                                if (!department) {
                                    return [];
                                }

                                return [
                                    `Test participants: ${department.participants}`,
                                    `Active students: ${department.active_students} / ${department.students}`,
                                    `Pass rate (latest score ≥ 75%): ${department.pass_rate === null ? 'No data' : `${department.pass_rate}%`}`,
                                ];
                            },
                        },
                    },
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        max: 100,
                        title: { display: true, text: 'Average score (%)' },
                        grid: { color: '#edf0f4' },
                    },
                    y: { grid: { display: false } },
                },
            },
        });
    }
});
