// Web Worker for processing chart data
self.onmessage = function(e) {
    const { type, data } = e.data;

    switch (type) {
        case 'processChartData':
            const processedData = processChartData(data);
            self.postMessage({ type: 'chartDataProcessed', data: processedData });
            break;

        case 'calculateStatistics':
            const stats = calculateStatistics(data);
            self.postMessage({ type: 'statisticsCalculated', data: stats });
            break;
    }
};

// Process chart data without blocking the main thread
function processChartData(data) {
    // Process and transform chart data
    const processed = {
        labels: [],
        datasets: []
    };

    // Process labels
    if (data.labels) {
        processed.labels = data.labels.map(label => 
            typeof label === 'string' ? label.trim() : label
        );
    }

    // Process datasets
    if (data.datasets) {
        processed.datasets = data.datasets.map(dataset => ({
            ...dataset,
            data: dataset.data.map(value => Number(value) || 0)
        }));
    }

    return processed;
}

// Calculate statistics for the dashboard
function calculateStatistics(data) {
    const stats = {
        total: 0,
        average: 0,
        max: Number.MIN_SAFE_INTEGER,
        min: Number.MAX_SAFE_INTEGER
    };

    if (Array.isArray(data)) {
        stats.total = data.reduce((sum, val) => sum + (Number(val) || 0), 0);
        stats.average = stats.total / data.length;
        stats.max = Math.max(...data.map(val => Number(val) || 0));
        stats.min = Math.min(...data.map(val => Number(val) || 0));
    }

    return stats;
}
