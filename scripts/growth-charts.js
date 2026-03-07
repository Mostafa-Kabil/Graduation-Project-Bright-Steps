/**
 * Bright Steps – Interactive Growth Charts
 * Uses Chart.js to visualize height, weight, and head circumference over time
 */

function initGrowthCharts(growthHistory) {
    if (!growthHistory || growthHistory.length === 0) return;

    const canvas = document.getElementById('growth-chart-canvas');
    if (!canvas) return;

    // Parse data
    const labels = growthHistory.map(r => {
        const d = new Date(r.recorded_at);
        return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: '2-digit' });
    });

    const heights = growthHistory.map(r => r.height ? parseFloat(r.height) : null);
    const weights = growthHistory.map(r => r.weight ? parseFloat(r.weight) : null);
    const heads = growthHistory.map(r => r.head_circumference ? parseFloat(r.head_circumference) : null);

    const ctx = canvas.getContext('2d');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Height (cm)',
                    data: heights,
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    borderWidth: 2.5,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointHoverRadius: 7,
                    pointBackgroundColor: '#6366f1',
                    yAxisID: 'y',
                },
                {
                    label: 'Weight (kg)',
                    data: weights,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 2.5,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointHoverRadius: 7,
                    pointBackgroundColor: '#10b981',
                    yAxisID: 'y1',
                },
                {
                    label: 'Head (cm)',
                    data: heads,
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245, 158, 11, 0.05)',
                    borderWidth: 2,
                    fill: false,
                    tension: 0.4,
                    pointRadius: 3,
                    pointHoverRadius: 6,
                    pointBackgroundColor: '#f59e0b',
                    borderDash: [5, 5],
                    yAxisID: 'y',
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        padding: 15,
                        font: { size: 12, family: "'Inter', sans-serif" },
                        color: getComputedStyle(document.documentElement).getPropertyValue('--text-primary').trim() || '#1e293b',
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(30, 41, 59, 0.9)',
                    titleFont: { size: 13, family: "'Inter', sans-serif" },
                    bodyFont: { size: 12, family: "'Inter', sans-serif" },
                    padding: 12,
                    cornerRadius: 8,
                    displayColors: true,
                    callbacks: {
                        label: function (context) {
                            let label = context.dataset.label || '';
                            if (context.parsed.y !== null) {
                                const unit = label.includes('kg') ? ' kg' : ' cm';
                                label += ': ' + context.parsed.y.toFixed(1) + unit;
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: {
                        font: { size: 11, family: "'Inter', sans-serif" },
                        color: '#94a3b8',
                        maxRotation: 45,
                    }
                },
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'cm',
                        font: { size: 12, family: "'Inter', sans-serif" },
                        color: '#6366f1',
                    },
                    grid: { color: 'rgba(148, 163, 184, 0.1)' },
                    ticks: {
                        font: { size: 11 },
                        color: '#94a3b8',
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'kg',
                        font: { size: 12, family: "'Inter', sans-serif" },
                        color: '#10b981',
                    },
                    grid: { drawOnChartArea: false },
                    ticks: {
                        font: { size: 11 },
                        color: '#94a3b8',
                    }
                }
            },
            animation: {
                duration: 800,
                easing: 'easeInOutQuart',
            }
        }
    });
}

// Auto-init when dashboard data is available
document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
        if (window.dashboardData && window.dashboardData.children) {
            const selectedChild = window.dashboardData.children[0];
            if (selectedChild && selectedChild.growth_history && selectedChild.growth_history.length > 0) {
                initGrowthCharts(selectedChild.growth_history);
            }
        }
    }, 500);
});

// Allow switching child
function updateGrowthChart(childIndex) {
    if (!window.dashboardData || !window.dashboardData.children) return;
    const child = window.dashboardData.children[childIndex];
    if (!child || !child.growth_history) return;

    const canvas = document.getElementById('growth-chart-canvas');
    if (!canvas) return;

    // Destroy existing chart
    const existingChart = Chart.getChart(canvas);
    if (existingChart) existingChart.destroy();

    initGrowthCharts(child.growth_history);
}
