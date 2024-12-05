// Chart state and configuration management
const chartState = {
    cache: new Map(),
    loading: new Set(),
    observers: new Map(),
    // Configure page sizes for different chart types
    pageSizes: {
        categoryDistributionChart: 20,     // Increased for better category overview
        orderAnalyticsChart: 45,          // 1.5 months of daily data (balanced view)
        stockTrendsChart: 45,             // 1.5 months of stock data
        movementChart: 24,                // Show last 24 movements
        recentActivityChart: 10,          // Show 10 most recent activities
        default: 30                       // Increased default for better initial view
    },
    // Maximum number of data points to keep in memory
    maxDataPoints: {
        categoryDistributionChart: 40,     // Support more categories
        orderAnalyticsChart: 180,         // 6 months of order history (most relevant period)
        stockTrendsChart: 180,            // 6 months of stock history
        movementChart: 100,               // More recent movements focus
        recentActivityChart: 50,          // Recent activities history
        default: 120                      // Increased default capacity
    },
    // Performance optimization settings
    performance: {
        cleanupThreshold: 0.80,           // More aggressive cleanup at 80%
        batchSize: 50,                    // Process 50 items per batch
        debounceTime: 250,                // 250ms debounce for updates
        throttleTime: 1000,               // 1 second throttle for intensive operations
        workerThreshold: 1000             // Use worker for datasets larger than 1000 points
    },
    // Animation durations (milliseconds)
    animation: {
        initial: 600,                     // Faster initial load
        update: 150,                      // Quicker updates
        none: 0,                          // No animation
        transition: 300                   // Smooth transitions
    },
    // Refresh intervals (milliseconds)
    refreshIntervals: {
        orderAnalyticsChart: 180000,      // Every 3 minutes
        stockTrendsChart: 120000,         // Every 2 minutes
        categoryDistributionChart: 300000, // Every 5 minutes
        movementChart: 60000,             // Every minute
        recentActivityChart: 30000        // Every 30 seconds
    },
    // Data retention periods (days)
    dataRetention: {
        orderAnalyticsChart: 180,         // 6 months (reduced from 1 year for better performance)
        stockTrendsChart: 180,            // 6 months
        movementChart: 90,                // 3 months
        recentActivityChart: 30,          // 1 month
        default: 60                       // 2 months
    },
    // Cache settings
    cache: {
        maxAge: 300000,                   // 5 minutes cache lifetime
        maxSize: 1000,                    // Maximum cache entries
        cleanupInterval: 600000           // Clean cache every 10 minutes
    },
    // Error handling
    errorHandling: {
        retryAttempts: 3,                 // Number of retry attempts
        retryDelay: 1000,                 // Delay between retries (ms)
        fallbackThreshold: 5              // Switch to fallback after 5 failures
    }
};

// Get page size for specific chart
function getPageSize(chartId) {
    return chartState.pageSizes[chartId] || chartState.pageSizes.default;
}

// Get max data points for specific chart
function getMaxDataPoints(chartId) {
    return chartState.maxDataPoints[chartId] || chartState.maxDataPoints.default;
}

// Check if cleanup is needed
function needsCleanup(chartId, currentDataLength) {
    const maxPoints = getMaxDataPoints(chartId);
    return currentDataLength >= (maxPoints * chartState.performance.cleanupThreshold);
}

// Cleanup old data points
function cleanupChartData(chart, chartId) {
    const maxPoints = getMaxDataPoints(chartId);
    const currentLength = chart.data.labels.length;
    
    if (currentLength > maxPoints) {
        const removeCount = currentLength - maxPoints;
        chart.data.labels.splice(0, removeCount);
        chart.data.datasets.forEach(dataset => {
            dataset.data.splice(0, removeCount);
        });
    }
}

// Enhanced updateChartData function with cleanup
function updateChartData(chart, newData, chartId) {
    if (!chart || !newData) return;

    requestAnimationFrame(() => {
        // Check if cleanup is needed
        if (needsCleanup(chartId, chart.data.labels.length)) {
            cleanupChartData(chart, chartId);
        }

        // Append new data
        if (newData.labels) {
            chart.data.labels.push(...newData.labels);
        }

        newData.datasets.forEach((newDataset, datasetIndex) => {
            if (chart.data.datasets[datasetIndex]) {
                chart.data.datasets[datasetIndex].data.push(...newDataset.data);
            }
        });

        // Update with appropriate animation
        const duration = chart.data.labels.length > getMaxDataPoints(chartId) / 2 
            ? chartState.animation.none 
            : chartState.animation.update;

        chart.update({
            duration: duration,
            easing: 'easeInOutQuart'
        });
    });
}

// Efficient data fetching with pagination and caching
async function fetchChartData(url, params = {}) {
    const cacheKey = `${url}?${new URLSearchParams(params).toString()}`;
    
    // Return cached data if available
    if (chartState.cache.has(cacheKey)) {
        return chartState.cache.get(cacheKey);
    }

    // Prevent multiple simultaneous requests for the same data
    if (chartState.loading.has(cacheKey)) {
        return null;
    }

    chartState.loading.add(cacheKey);

    try {
        const response = await fetch(`${url}?${new URLSearchParams(params).toString()}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        chartState.cache.set(cacheKey, data);
        return data;
    } catch (error) {
        console.error('Error fetching chart data:', error);
        return null;
    } finally {
        chartState.loading.delete(cacheKey);
    }
}

// Modified initializeChartWithPagination to use new configuration
async function initializeChartWithPagination(chartElement, apiUrl, config) {
    if (!chartElement) return null;

    const chartId = chartElement.id;
    const pageSize = getPageSize(chartId);

    const chartInstance = new Chart(chartElement, {
        ...config,
        data: {
            labels: [],
            datasets: config.data.datasets.map(dataset => ({
                ...dataset,
                data: []
            }))
        },
        options: {
            ...config.options,
            animation: {
                duration: chartState.animation.initial
            },
            transitions: {
                active: {
                    animation: {
                        duration: chartState.animation.update
                    }
                }
            }
        }
    });

    // Initialize pagination state
    const paginationState = {
        currentPage: 1,
        hasMore: true,
        isLoading: false,
        totalDataPoints: 0
    };

    // Create intersection observer for lazy loading
    const observer = new IntersectionObserver(async (entries) => {
        const entry = entries[0];
        if (entry.isIntersecting && !paginationState.isLoading && paginationState.hasMore) {
            await loadMoreData();
        }
    }, {
        threshold: 0.1,
        rootMargin: '50px'
    });

    chartState.observers.set(chartElement, observer);
    observer.observe(chartElement);

    // Enhanced loadMoreData function
    async function loadMoreData() {
        if (paginationState.isLoading || !paginationState.hasMore) return;

        paginationState.isLoading = true;
        
        try {
            const newData = await fetchChartData(apiUrl, {
                page: paginationState.currentPage,
                pageSize: pageSize
            });

            if (newData && newData.data && newData.data.length > 0) {
                updateChartData(chartInstance, newData, chartId);
                paginationState.currentPage++;
                paginationState.totalDataPoints += newData.data.length;
                paginationState.hasMore = newData.data.length === pageSize && 
                                        paginationState.totalDataPoints < getMaxDataPoints(chartId);
            } else {
                paginationState.hasMore = false;
            }
        } catch (error) {
            console.error('Error loading more data:', error);
        } finally {
            paginationState.isLoading = false;
        }
    }

    // Initial data load
    await loadMoreData();

    return chartInstance;
}

// Cleanup function to remove observers and clear cache
function cleanupChart(chartElement) {
    const observer = chartState.observers.get(chartElement);
    if (observer) {
        observer.disconnect();
        chartState.observers.delete(chartElement);
    }
}

// Initialize dashboard with optimized data handling
document.addEventListener('DOMContentLoaded', async function() {
    const charts = {
        category: await initializeChartWithPagination(
            document.getElementById('categoryDistributionChart'),
            '/api/category-data',
            chartConfigs.categoryDistributionChart
        ),
        orders: await initializeChartWithPagination(
            document.getElementById('orderAnalyticsChart'),
            '/api/order-data',
            chartConfigs.orderAnalyticsChart
        ),
        stock: await initializeChartWithPagination(
            document.getElementById('stockTrendsChart'),
            '/api/stock-data',
            chartConfigs.stockTrendsChart
        )
    };

    // Cleanup on page unload
    window.addEventListener('unload', () => {
        Object.values(charts).forEach(chart => {
            if (chart && chart.canvas) {
                cleanupChart(chart.canvas);
            }
        });
    });
});
