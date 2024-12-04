// Dashboard initialization and charts
document.addEventListener('DOMContentLoaded', function() {
    // Stock Trends Chart
    const stockTrendsCtx = document.getElementById('stockTrendsChart').getContext('2d');
    let stockTrendsChart;

    function renderStockTrendsChart(labels, dataIn, dataOut) {
        if (stockTrendsChart) stockTrendsChart.destroy();
        
        stockTrendsChart = new Chart(stockTrendsCtx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Entrées',
                    data: dataIn,
                    borderColor: 'rgb(59, 130, 246)',
                    tension: 0.1
                }, {
                    label: 'Sorties',
                    data: dataOut,
                    borderColor: 'rgb(239, 68, 68)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.dataset.label}: ${context.raw} unités`;
                            }
                        }
                    },
                    legend: {
                        position: 'top',
                        labels: {
                            color: document.documentElement.classList.contains('dark') ? '#fff' : '#666'
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: document.documentElement.classList.contains('dark') ? '#374151' : '#e5e7eb'
                        },
                        ticks: {
                            color: document.documentElement.classList.contains('dark') ? '#fff' : '#666'
                        }
                    },
                    x: {
                        grid: {
                            color: document.documentElement.classList.contains('dark') ? '#374151' : '#e5e7eb'
                        },
                        ticks: {
                            color: document.documentElement.classList.contains('dark') ? '#fff' : '#666'
                        }
                    }
                }
            }
        });
    }

    // Initial render of stock trends chart
    renderStockTrendsChart(movementDates, movementDataIn, movementDataOut);

    // Toggle time frames for stock trends
    document.querySelectorAll('.time-frame-toggle').forEach(button => {
        button.addEventListener('click', function() {
            const timeframe = this.getAttribute('data-timeframe');
            
            // Update active button state
            document.querySelectorAll('.time-frame-toggle').forEach(btn => {
                if (btn === this) {
                    btn.classList.remove('bg-gray-200', 'dark:bg-gray-700', 'text-gray-800');
                    btn.classList.add('bg-blue-500', 'text-white');
                } else {
                    btn.classList.remove('bg-blue-500', 'text-white');
                    btn.classList.add('bg-gray-200', 'dark:bg-gray-700', 'text-gray-800');
                }
            });

            // Update chart data based on timeframe
            if (timeframe === 'monthly') {
                renderStockTrendsChart(monthlyMovementDates, monthlyMovementDataIn, monthlyMovementDataOut);
            } else {
                renderStockTrendsChart(movementDates, movementDataIn, movementDataOut);
            }
        });
    });

    // Initialize Category Distribution Chart
    const categoryCtx = document.getElementById('categoryDistributionChart').getContext('2d');
    new Chart(categoryCtx, {
        type: 'doughnut',
        data: {
            labels: categoryLabels,
            datasets: [{
                data: categoryData,
                backgroundColor: [
                    'rgba(59, 130, 246, 0.8)',
                    'rgba(16, 185, 129, 0.8)',
                    'rgba(249, 115, 22, 0.8)',
                    'rgba(139, 92, 246, 0.8)',
                    'rgba(236, 72, 153, 0.8)'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right'
                }
            }
        }
    });

    // Initialize Movement Data Chart
    const movementCtx = document.getElementById('movementChart').getContext('2d');
    new Chart(movementCtx, {
        type: 'bar',
        data: {
            labels: movementDates,
            datasets: [
                {
                    label: 'Entrée',
                    data: movementDataIn,
                    backgroundColor: 'rgba(52, 152, 219, 0.8)'
                },
                {
                    label: 'Sortie',
                    data: movementDataOut,
                    backgroundColor: 'rgba(231, 76, 60, 0.8)'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `${context.dataset.label}: ${context.raw}`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: document.documentElement.classList.contains('dark') ? '#374151' : '#e5e7eb'
                    },
                    ticks: {
                        color: document.documentElement.classList.contains('dark') ? '#fff' : '#666'
                    }
                },
                x: {
                    grid: {
                        color: document.documentElement.classList.contains('dark') ? '#374151' : '#e5e7eb'
                    },
                    ticks: {
                        color: document.documentElement.classList.contains('dark') ? '#fff' : '#666'
                    }
                }
            }
        }
    });

    // Initialize Order Analytics Chart
    const orderAnalyticsCtx = document.getElementById('orderAnalyticsChart').getContext('2d');
    let orderAnalyticsChart = new Chart(orderAnalyticsCtx, {
        type: 'line',
        data: {
            labels: orderVolumeData.dates,
            datasets: [
                {
                    label: 'Total des commandes',
                    data: orderVolumeData.total,
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    tension: 0.4,
                    fill: true,
                    order: 4
                },
                {
                    label: 'Commandes en attente',
                    data: orderVolumeData.pending,
                    borderColor: '#f39c12',
                    backgroundColor: 'rgba(243, 156, 18, 0.1)',
                    tension: 0.4,
                    fill: true,
                    order: 3
                },
                {
                    label: 'Commandes en cours',
                    data: orderVolumeData.processing,
                    borderColor: '#9b59b6',
                    backgroundColor: 'rgba(155, 89, 182, 0.1)',
                    tension: 0.4,
                    fill: true,
                    order: 2
                },
                {
                    label: 'Commandes terminées',
                    data: orderVolumeData.completed,
                    borderColor: '#2ecc71',
                    backgroundColor: 'rgba(46, 204, 113, 0.1)',
                    tension: 0.4,
                    fill: true,
                    order: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index'
            },
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        padding: 15
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `${context.dataset.label}: ${context.raw} commandes`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: document.documentElement.classList.contains('dark') ? '#374151' : '#e5e7eb'
                    },
                    ticks: {
                        color: document.documentElement.classList.contains('dark') ? '#fff' : '#666'
                    }
                },
                x: {
                    grid: {
                        color: document.documentElement.classList.contains('dark') ? '#374151' : '#e5e7eb'
                    },
                    ticks: {
                        color: document.documentElement.classList.contains('dark') ? '#fff' : '#666'
                    }
                }
            }
        }
    });

    function fetchOrderData() {
        fetch(orderUpdateUrl)
            .then(response => response.json())
            .then(data => {
                // Update the chart with new data
                orderAnalyticsChart.data.datasets[0].data = data.total;
                orderAnalyticsChart.data.datasets[1].data = data.pending;
                orderAnalyticsChart.data.datasets[2].data = data.processing;
                orderAnalyticsChart.data.datasets[3].data = data.completed;
                orderAnalyticsChart.update();
            })
            .catch(error => console.error('Error fetching order data:', error));
    }

    // Poll every 30 seconds
    setInterval(fetchOrderData, 30000);

    function updateDashboard() {
        const params = new URLSearchParams({
            search: searchBar.value,
            status: statusFilter.value,
            category: categoryFilter.value,
            date: dateFilter.value
        });

        fetch(`${window.location.pathname}?${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                // Update statistics
                document.querySelector('[data-target="totalEquipment"]').textContent = data.stats.totalEquipment;
                document.querySelector('[data-target="activeEquipment"]').textContent = data.stats.activeEquipment;
                document.querySelector('[data-target="lowStockCount"]').textContent = data.stats.lowStockCount;
                document.querySelector('[data-target="pendingOrders"]').textContent = data.stats.pendingOrders;
                document.querySelector('[data-target="totalStockValue"]').textContent = 
                    new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(data.stats.totalStockValue);

                // Update charts
                if (data.movementDates && data.movementDataIn && data.movementDataOut) {
                    renderStockTrendsChart(data.movementDates, data.movementDataIn, data.movementDataOut);
                }
            })
            .catch(error => console.error('Error updating dashboard:', error));
    }

    // Event listeners for filters
    searchButton.addEventListener('click', updateDashboard);
    searchBar.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') updateDashboard();
    });
    statusFilter.addEventListener('change', updateDashboard);
    categoryFilter.addEventListener('change', updateDashboard);
    dateFilter.addEventListener('change', updateDashboard);

    // Counter animation
    function animateCounter(element) {
        const target = parseFloat(element.getAttribute('data-target'));
        const duration = 1000;
        const start = 0;
        const increment = target / (duration / 16);
        let current = start;

        const animate = () => {
            current += increment;
            if (current < target) {
                element.textContent = Math.floor(current);
                requestAnimationFrame(animate);
            } else {
                element.textContent = target;
            }
        };

        animate();
    }

    // Animate all counters on page load
    document.querySelectorAll('.counter').forEach(counter => {
        animateCounter(counter);
    });

    function showToast(message, type = 'info') {
        const toastContainer = document.getElementById('toast-container');
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <div class="icon">
                <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i>
            </div>
            <div>${message}</div>
        `;
        toastContainer.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 300);
        }, 5000);
    }

    // Example toast notifications
    showToast('Low stock alert for Item XYZ', 'error');
    showToast('New order received', 'success');
});
