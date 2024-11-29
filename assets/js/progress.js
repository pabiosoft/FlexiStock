class ProgressBar {
    constructor(element, options = {}) {
        this.container = element;
        this.options = {
            value: options.value || 0,
            max: options.max || 100,
            showLabel: options.showLabel || false,
            size: options.size || 'md',
            color: options.color || 'primary',
            animated: options.animated || false
        };
        this.init();
    }

    init() {
        // Create progress bar elements
        this.progressBar = document.createElement('div');
        this.progressBar.className = `theme-progress theme-progress-${this.options.size} theme-progress-${this.options.color}`;
        
        if (this.options.animated) {
            this.progressBar.classList.add('theme-progress-animated');
        }

        if (this.options.showLabel) {
            this.container.classList.add('theme-progress-labeled');
            this.label = document.createElement('span');
            this.label.className = 'theme-progress-label';
            this.container.appendChild(this.label);
        }

        // Add progress bar to container
        this.container.classList.add('theme-progress-container');
        this.container.appendChild(this.progressBar);

        // Set initial value
        this.setValue(this.options.value);
    }

    setValue(value) {
        const percentage = (value / this.options.max) * 100;
        this.progressBar.style.width = `${percentage}%`;
        
        if (this.options.showLabel) {
            this.label.textContent = `${Math.round(percentage)}%`;
        }
    }

    setColor(color) {
        this.progressBar.className = this.progressBar.className.replace(/theme-progress-\w+/, `theme-progress-${color}`);
    }

    setAnimated(animated) {
        if (animated) {
            this.progressBar.classList.add('theme-progress-animated');
        } else {
            this.progressBar.classList.remove('theme-progress-animated');
        }
    }
}

// Initialize progress bars
document.addEventListener('DOMContentLoaded', () => {
    // Auto-initialize progress bars with data attributes
    document.querySelectorAll('[data-progress]').forEach(container => {
        const options = {
            value: parseInt(container.dataset.value || 0),
            max: parseInt(container.dataset.max || 100),
            showLabel: container.dataset.showLabel === 'true',
            size: container.dataset.size || 'md',
            color: container.dataset.color || 'primary',
            animated: container.dataset.animated === 'true'
        };
        new ProgressBar(container, options);
    });
});
