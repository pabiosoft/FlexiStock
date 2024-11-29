class AdsLoader {
    constructor() {
        this.container = document.querySelector('.ads-container');
        this.progressContainer = document.querySelector('.ads-progress-container');
        this.progressBar = document.querySelector('.ads-progress-bar');
        this.progressText = document.querySelector('.ads-progress-text');
        this.adsGrid = document.querySelector('.ads-grid');
        this.errorMessage = document.querySelector('.ads-error');
        this.retryCount = 0;
        this.maxRetries = 3;
        this.refreshInterval = 5 * 60 * 1000; // 5 minutes
        this.isLoading = false;
        this.loadingStartTime = 0;
        
        // Initialize
        this.init();
    }

    init() {
        // Load ads immediately when page loads
        this.loadAds();

        // Set up periodic refresh
        setInterval(() => this.loadAds(), this.refreshInterval);

        // Listen for dashboard updates
        document.addEventListener('dashboard-updated', () => this.loadAds());

        // Refresh ads when dashboard refreshes
        document.querySelector('.refresh-button')?.addEventListener('click', () => {
            this.loadAds();
        });

        // Add retry button listener
        document.querySelector('.retry-button')?.addEventListener('click', () => {
            this.retryLoading();
        });
    }

    showProgress() {
        this.progressContainer.classList.add('loading');
        this.progressBar.classList.add('loading');
        this.loadingStartTime = Date.now();
        this.updateProgressText('Chargement des annonces...');
    }

    hideProgress() {
        this.progressContainer.classList.remove('loading');
        this.progressBar.classList.remove('loading');
        this.updateProgressText('');
    }

    updateProgressText(text) {
        if (this.progressText) {
            this.progressText.textContent = text;
        }
    }

    async loadAds() {
        if (this.isLoading) return;
        
        this.isLoading = true;
        this.showProgress();
        this.hideError();

        try {
            const response = await fetch('/dashboard/ads', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.message || 'Failed to load ads');
            }

            // Ensure minimum loading time of 500ms for better UX
            const loadingTime = Date.now() - this.loadingStartTime;
            if (loadingTime < 500) {
                await new Promise(resolve => setTimeout(resolve, 500 - loadingTime));
            }

            this.displayAds(data.ads);
            this.retryCount = 0;
        } catch (error) {
            console.error('Error loading ads:', error);
            this.showError(error.message);
            
            if (this.retryCount < this.maxRetries) {
                this.retryCount++;
                this.updateProgressText('Nouvelle tentative...');
                setTimeout(() => this.loadAds(), 2000);
            }
        } finally {
            this.hideProgress();
            this.isLoading = false;
        }
    }

    showError(message, isRetryable = true) {
        const errorHtml = `
            <div class="error-icon">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <h4 class="error-title">Erreur de chargement</h4>
            <p class="error-message">${message}</p>
            ${isRetryable ? '<button class="retry-button">Réessayer</button>' : ''}
        `;

        this.errorMessage.innerHTML = errorHtml;
        this.errorMessage.style.display = 'flex';
        this.adsGrid.style.display = 'none';
    }

    hideError() {
        this.errorMessage.style.display = 'none';
        this.adsGrid.style.display = 'grid';
    }

    async retryLoading() {
        if (this.retryCount < this.maxRetries) {
            this.retryCount++;
            await new Promise(resolve => setTimeout(resolve, 2000));
            this.loadAds();
        } else {
            this.showError('Le nombre maximum de tentatives a été atteint. Veuillez réessayer plus tard.', false);
        }
    }

    displayAds(ads) {
        const grid = document.querySelector('.ads-grid');
        grid.innerHTML = '';

        ads.forEach((ad, index) => {
            const card = this.createAdCard(ad);
            grid.appendChild(card);
            
            // Stagger animation of cards
            setTimeout(() => card.classList.add('visible'), 100 * index);
        });
    }

    createAdCard(ad) {
        const card = document.createElement('div');
        card.className = 'ad-card';
        
        const badge = ad.badge ? `<span class="ad-badge">${ad.badge}</span>` : '';
        const date = ad.endDate ? 
            `<span class="ad-date">Jusqu'au ${new Date(ad.endDate).toLocaleDateString()}</span>` :
            `<span class="ad-date">${new Date(ad.date).toLocaleDateString()}</span>`;

        card.innerHTML = `
            <div class="ad-image">
                <img src="${ad.image || '/build/images/placeholder.jpg'}" alt="${ad.title}">
                ${badge}
            </div>
            <div class="ad-content">
                <h3>${ad.title}</h3>
                <p>${ad.description}</p>
                ${date}
            </div>
        `;

        return card;
    }
}

// Initialize AdsLoader when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.adsLoader = new AdsLoader();
});
