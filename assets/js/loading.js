class LoadingManager {
    constructor() {
        this.initializeElements();
        this.bindEvents();
        this.autoRefreshInterval = null;
        this.autoRefreshTime = 30; // seconds
    }

    initializeElements() {
        // Create loading bar
        this.loadingBar = document.createElement('div');
        this.loadingBar.className = 'loading-progress';
        this.loadingBar.style.display = 'none';

        // Create loading overlay
        this.overlay = document.createElement('div');
        this.overlay.className = 'loading-overlay';
        this.overlay.innerHTML = '<div class="loading-spinner"></div>';

        // Create refresh progress
        this.refreshContainer = document.createElement('div');
        this.refreshContainer.className = 'refresh-progress-container';
        this.refreshProgress = document.createElement('div');
        this.refreshProgress.className = 'refresh-progress';
        this.refreshContainer.appendChild(this.refreshProgress);

        // Create refresh indicator
        this.refreshIndicator = document.createElement('div');
        this.refreshIndicator.className = 'refresh-indicator';
        this.refreshIndicator.innerHTML = '<i class="fas fa-sync-alt"></i> <span>Actualisation dans <span class="refresh-countdown">30</span>s</span>';

        // Add elements to DOM
        document.body.appendChild(this.loadingBar);
        document.body.appendChild(this.overlay);

        // Add refresh elements to dashboard if it exists
        const dashboard = document.querySelector('.dashboard-header');
        if (dashboard) {
            dashboard.appendChild(this.refreshIndicator);
            dashboard.appendChild(this.refreshContainer);
        }
    }

    bindEvents() {
        // Show loading on navigation
        document.addEventListener('turbo:before-fetch-request', () => this.showLoading());
        document.addEventListener('turbo:before-fetch-response', () => this.hideLoading());

        // Show loading on form submissions
        document.addEventListener('submit', () => this.showLoading());

        // Handle AJAX requests
        this.interceptAjaxRequests();
    }

    showLoading() {
        this.loadingBar.style.display = 'block';
        this.overlay.classList.add('active');
    }

    hideLoading() {
        this.loadingBar.style.display = 'none';
        this.overlay.classList.remove('active');
    }

    startAutoRefresh() {
        if (this.autoRefreshInterval) return;

        let timeLeft = this.autoRefreshTime;
        const countdown = document.querySelector('.refresh-countdown');
        const progress = this.refreshProgress;

        this.autoRefreshInterval = setInterval(() => {
            timeLeft--;
            if (countdown) countdown.textContent = timeLeft;
            
            // Update progress bar
            const percentage = ((this.autoRefreshTime - timeLeft) / this.autoRefreshTime) * 100;
            progress.style.width = `${percentage}%`;

            if (timeLeft <= 0) {
                this.refresh();
                timeLeft = this.autoRefreshTime;
            }
        }, 1000);
    }

    stopAutoRefresh() {
        if (this.autoRefreshInterval) {
            clearInterval(this.autoRefreshInterval);
            this.autoRefreshInterval = null;
        }
    }

    refresh() {
        this.showLoading();
        window.location.reload();
    }

    interceptAjaxRequests() {
        const originalXHR = window.XMLHttpRequest;
        const self = this;

        window.XMLHttpRequest = function() {
            const xhr = new originalXHR();
            xhr.addEventListener('loadstart', () => self.showLoading());
            xhr.addEventListener('loadend', () => self.hideLoading());
            return xhr;
        };
    }
}

// Initialize loading manager
document.addEventListener('DOMContentLoaded', () => {
    window.loadingManager = new LoadingManager();
    
    // Start auto-refresh if on dashboard
    if (document.querySelector('.dashboard-content')) {
        window.loadingManager.startAutoRefresh();
    }
});
