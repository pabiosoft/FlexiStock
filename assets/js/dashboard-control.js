class DashboardControl {
    constructor() {
        this.loadingOverlay = null;
        this.progressBar = null;
        this.refreshTimer = null;
        this.refreshInterval = 30000; // 30 seconds
        this.currentProgress = 0;

        this.init();
    }

    init() {
        this.createLoadingElements();
        this.attachEventListeners();
        this.startRefreshTimer();
    }

    createLoadingElements() {
        // Create loading overlay
        this.loadingOverlay = document.createElement('div');
        this.loadingOverlay.className = 'loading-overlay hidden';
        this.loadingOverlay.innerHTML = `
            <div class="loading-spinner">
                <div class="spinner"></div>
                <div class="loading-text">Loading...</div>
            </div>
        `;

        // Create progress bar
        this.progressBar = document.createElement('div');
        this.progressBar.className = 'refresh-progress-bar';
        this.progressBar.innerHTML = `
            <div class="progress-inner"></div>
            <div class="progress-text">30s</div>
        `;

        // Add elements to DOM
        document.body.appendChild(this.loadingOverlay);
        document.querySelector('.dashboard-header')?.appendChild(this.progressBar);
    }

    attachEventListeners() {
        // Refresh button click handler
        document.querySelector('.refresh-button')?.addEventListener('click', () => {
            this.refresh();
        });
    }

    showLoading() {
        this.loadingOverlay.classList.remove('hidden');
    }

    hideLoading() {
        this.loadingOverlay.classList.add('hidden');
    }

    updateProgress(progress) {
        this.currentProgress = progress;
        const progressInner = this.progressBar.querySelector('.progress-inner');
        const progressText = this.progressBar.querySelector('.progress-text');
        
        progressInner.style.width = `${progress}%`;
        progressText.textContent = `${Math.ceil((this.refreshInterval - (progress * this.refreshInterval / 100)) / 1000)}s`;
    }

    startRefreshTimer() {
        if (this.refreshTimer) {
            clearInterval(this.refreshTimer);
        }

        this.currentProgress = 0;
        this.updateProgress(0);

        const step = 100 / (this.refreshInterval / 100); // Update every 100ms
        
        this.refreshTimer = setInterval(() => {
            this.currentProgress += step;
            
            if (this.currentProgress >= 100) {
                this.refresh();
            } else {
                this.updateProgress(this.currentProgress);
            }
        }, 100);
    }

    async refresh() {
        try {
            this.showLoading();
            
            const response = await fetch('/dashboard/refresh', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error('Network response was not ok');
            }

            const data = await response.json();
            
            // Update statistics
            Object.entries(data.stats).forEach(([key, value]) => {
                const element = document.querySelector(`[data-stat="${key}"]`);
                if (element) {
                    element.textContent = value;
                }
            });

            // Update charts
            if (window.stockMovementChart) {
                window.stockMovementChart.data.labels = data.movements.dates;
                window.stockMovementChart.data.datasets[0].data = data.movements.dataIn;
                window.stockMovementChart.data.datasets[1].data = data.movements.dataOut;
                window.stockMovementChart.update();
            }

            if (window.categoryDistributionChart) {
                window.categoryDistributionChart.data.labels = data.categories.labels;
                window.categoryDistributionChart.data.datasets[0].data = data.categories.data;
                window.categoryDistributionChart.update();
            }

            // Restart refresh timer
            this.startRefreshTimer();
        } catch (error) {
            console.error('Error refreshing dashboard:', error);
        } finally {
            this.hideLoading();
        }
    }
}

// Initialize dashboard control when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.dashboardControl = new DashboardControl();
});
