// Dashboard initialization and charts with lazy loading
document.addEventListener('DOMContentLoaded', function() {
    // Chart instances storage
    let charts = {};

    // Intersection Observer for lazy loading
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const chartId = entry.target.id;
                initializeChart(chartId);
                observer.unobserve(entry.target);
            }
        });
    }, {
        root: null,
        rootMargin: '50px',
        threshold: 0.1
    });

    // Observe all chart canvases
    document.querySelectorAll('canvas[id$="Chart"]').forEach(canvas => {
        observer.observe(canvas);
    });

    function initializeChart(chartId) {
        const ctx = document.getElementById(chartId).getContext('2d');
        
        const sharedOptions = {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 750 },
            devicePixelRatio: 2
        };

        switch(chartId) {
            case 'stockTrendsChart':
                charts[chartId] = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: movementDates,
                        datasets: [{
                            label: 'Entrées',
                            data: movementDataIn,
                            borderColor: 'rgb(59, 130, 246)',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            tension: 0.1
                        }, {
                            label: 'Sorties',
                            data: movementDataOut,
                            borderColor: 'rgb(239, 68, 68)',
                            backgroundColor: 'rgba(239, 68, 68, 0.1)',
                            tension: 0.1
                        }]
                    },
                    options: {
                        ...sharedOptions,
                        interaction: {
                            intersect: false,
                            mode: 'index'
                        },
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
                break;

            case 'categoryDistributionChart':
                charts[chartId] = new Chart(ctx, {
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
                        ...sharedOptions,
                        cutout: '65%',
                        plugins: {
                            legend: {
                                position: 'right'
                            }
                        }
                    }
                });
                break;

            case 'movementChart':
                charts[chartId] = new Chart(ctx, {
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
                        ...sharedOptions,
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
                break;

            case 'orderAnalyticsChart':
                charts[chartId] = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: orderVolumeData.dates,
                        datasets: [
                            {
                                label: 'Total des commandes',
                                data: orderVolumeData.total,
                                borderColor: '#3498db',
                                backgroundColor: 'rgba(52, 152, 219, 0.1)',
                                fill: true
                            },
                            {
                                label: 'Commandes en attente',
                                data: orderVolumeData.pending,
                                borderColor: '#f39c12',
                                backgroundColor: 'rgba(243, 156, 18, 0.1)',
                                fill: true
                            },
                            {
                                label: 'Commandes en cours',
                                data: orderVolumeData.processing,
                                borderColor: '#9b59b6',
                                backgroundColor: 'rgba(155, 89, 182, 0.1)',
                                fill: true
                            },
                            {
                                label: 'Commandes terminées',
                                data: orderVolumeData.completed,
                                borderColor: '#2ecc71',
                                backgroundColor: 'rgba(46, 204, 113, 0.1)',
                                fill: true
                            }
                        ]
                    },
                    options: {
                        ...sharedOptions,
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
                break;
        }
    }

    // Handle time frame toggles
    document.querySelectorAll('.time-frame-toggle').forEach(button => {
        button.addEventListener('click', function() {
            const timeframe = this.getAttribute('data-timeframe');
            const chart = charts['stockTrendsChart'];
            
            if (chart) {
                chart.data.labels = timeframe === 'monthly' ? monthlyMovementDates : movementDates;
                chart.data.datasets[0].data = timeframe === 'monthly' ? monthlyMovementDataIn : movementDataIn;
                chart.data.datasets[1].data = timeframe === 'monthly' ? monthlyMovementDataOut : movementDataOut;
                chart.update();
            }
        });
    });

    // Update order data periodically
    function updateOrderData() {
        fetch(orderUpdateUrl)
            .then(response => response.json())
            .then(data => {
                const chart = charts['orderAnalyticsChart'];
                if (chart) {
                    chart.data.datasets[0].data = data.total;
                    chart.data.datasets[1].data = data.pending;
                    chart.data.datasets[2].data = data.processing;
                    chart.data.datasets[3].data = data.completed;
                    chart.update();
                }
            })
            .catch(console.error);
    }

    setInterval(updateOrderData, 30000);

    // Fetch dashboard data
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
                    const chart = charts['stockTrendsChart'];
                    if (chart) {
                        chart.data.labels = data.movementDates;
                        chart.data.datasets[0].data = data.movementDataIn;
                        chart.data.datasets[1].data = data.movementDataOut;
                        chart.update();
                    }
                }
            })
            .catch(console.error);
    }
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
