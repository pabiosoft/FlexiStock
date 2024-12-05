// Test if the file is loaded
console.log('Notifications.js is loaded');

class NotificationManager {
    constructor() {
        console.log('NotificationManager constructor called');
        this.initializeElements();
        if (this.hasRequiredElements()) {
            this.notifications = [];
            this.init();
        } else {
            console.warn('Missing required elements for NotificationManager');
            // console.log('Toggle button:', this.toggleButton);
            // console.log('Dropdown:', this.dropdown);
            // console.log('Badge:', this.badge);
            // Create notification container if it doesn't exist
            if (!this.container) {
                this.container = document.createElement('div');
                this.container.id = 'notification-container';
                this.container.className = 'fixed top-4 right-4 z-50 space-y-2 w-96';
                document.body.appendChild(this.container);
                // console.log('Created notification container');
            }
        }
    }

    initializeElements() {
        // console.log('Initializing elements');
        this.container = document.getElementById('notification-container');
        this.badge = document.querySelector('.notification-badge');
        this.dropdown = document.getElementById('notification-dropdown');
        this.toggleButton = document.getElementById('notification-toggle');
        
        // console.log('Initialized elements:', {
        //     container: this.container,
        //     badge: this.badge,
        //     dropdown: this.dropdown,
        //     toggleButton: this.toggleButton
        // });
    }

    hasRequiredElements() {
        // Only check for toggle button and dropdown as they're essential for the notification menu
        const hasElements = this.toggleButton && this.dropdown;
        // console.log('Has required elements:', hasElements);
        return hasElements;
    }

    init() {
        // console.log('Initializing NotificationManager');
        this.fetchNotifications();
        setInterval(() => this.fetchNotifications(), 30000);

        if (this.toggleButton && this.dropdown) {
            // Toggle dropdown on button click
            this.toggleButton.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                // console.log('Toggle button clicked');
                this.toggleDropdown();
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', (e) => {
                if (this.dropdown && !this.dropdown.contains(e.target) && !this.toggleButton.contains(e.target)) {
                    // console.log('Clicking outside, closing dropdown');
                    this.dropdown.classList.add('hidden');
                }
            });

            // Handle notification actions
            this.dropdown.addEventListener('click', (e) => {
                const dismissButton = e.target.closest('[data-notification-dismiss]');
                const clearAllButton = e.target.closest('#clear-all-notifications');
                const priorityFilter = e.target.closest('[data-priority-filter]');

                if (dismissButton) {
                    e.preventDefault();
                    const notificationId = dismissButton.dataset.notificationId;
                    // console.log('Dismissing notification:', notificationId);
                    this.markAsRead(notificationId);
                }
                if (clearAllButton) {
                    e.preventDefault();
                    // console.log('Clearing all notifications');
                    this.clearAllNotifications();
                }
                if (priorityFilter) {
                    e.preventDefault();
                    const priority = priorityFilter.dataset.priority;
                    // console.log('Filtering by priority:', priority);
                    this.fetchNotificationsByPriority(priority);
                }
            });
        }
    }

    toggleDropdown() {
        // console.log('Toggling dropdown');
        if (this.dropdown) {
            const isHidden = this.dropdown.classList.contains('hidden');
            // console.log('Dropdown is hidden:', isHidden);
            if (isHidden) {
                // Position the dropdown relative to the toggle button
                const buttonRect = this.toggleButton.getBoundingClientRect();
                // console.log('Button rect:', buttonRect);
                this.dropdown.style.position = 'fixed';
                this.dropdown.style.top = `${buttonRect.bottom + window.scrollY}px`;
                this.dropdown.style.right = `${window.innerWidth - buttonRect.right}px`;
            }
            this.dropdown.classList.toggle('hidden');
            // console.log('Dropdown visibility toggled');
        }
    }

    async fetchNotifications() {
        try {
            // console.log('Fetching notifications');
            const response = await fetch('/notifications/fetch');
            // console.log('Fetch response:', response);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();
            // console.log('Fetched notifications data:', data);
            
            if (data.success) {
                this.notifications = data.data.notifications;
                this.updateUI();
                
                // Display new notifications in the container
                this.showNewNotifications();
            }
        } catch (error) {
            console.error('Error fetching notifications:', error);
        }
    }

    showNewNotifications() {
        if (!this.container) return;
        
        // Get new notifications (those that weren't displayed before)
        const newNotifications = this.notifications.filter(n => !n.displayed);
        
        newNotifications.forEach(notification => {
            // Mark as displayed
            notification.displayed = true;
            
            // Create notification element
            const notifElement = document.createElement('div');
            notifElement.className = `notification-toast bg-white dark:bg-gray-800 border-l-4 ${this.getPriorityClass(notification.priority)} p-4 rounded shadow-lg transform transition-all duration-300 opacity-0 translate-x-full`;
            notifElement.innerHTML = `
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        ${this.getNotificationIcon(notification.level)}
                    </div>
                    <div class="ml-3 flex-1">
                        <p class="text-sm text-gray-800 dark:text-gray-200">${notification.message}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">${this.formatDate(notification.createdAt)}</p>
                    </div>
                    <button class="ml-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" onclick="this.parentElement.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            
            // Add to container
            this.container.appendChild(notifElement);
            
            // Trigger animation
            setTimeout(() => {
                notifElement.classList.remove('opacity-0', 'translate-x-full');
            }, 100);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                notifElement.classList.add('opacity-0', 'translate-x-full');
                setTimeout(() => {
                    notifElement.remove();
                }, 300);
            }, 5000);
        });
    }

    async fetchNotificationsByPriority(priority) {
        try {
            // console.log('Fetching notifications by priority:', priority);
            const response = await fetch(`/notifications/priority/${priority}`);
            // console.log('Priority fetch response:', response);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();
            // console.log('Fetched notifications by priority:', data);
            if (data.notifications) {
                this.notifications = data.notifications;
                this.updateUI();
            }
        } catch (error) {
            console.error('Error fetching notifications by priority:', error);
        }
    }

    async markAsRead(notificationId) {
        try {
            // console.log('Marking notification as read:', notificationId);
            const response = await fetch(`/notifications/${notificationId}/mark-read`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            // console.log('Mark as read response:', response);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();
            // console.log('Mark as read result:', data);
            this.notifications = this.notifications.filter(n => n.id !== parseInt(notificationId));
            this.updateUI();
        } catch (error) {
            console.error('Error marking notification as read:', error);
        }
    }

    async clearAllNotifications() {
        try {
            // console.log('Clearing all notifications');
            const response = await fetch('/notifications/clear-all', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            // console.log('Clear all response:', response);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();
            // console.log('Clear all result:', data);
            if (data.success) {
                this.notifications = [];
                this.updateUI();
            }
        } catch (error) {
            console.error('Error clearing notifications:', error);
        }
    }

    getPriorityClass(priority) {
        const classes = {
            'high': 'border-red-500',
            'medium': 'border-yellow-500',
            'low': 'border-blue-500'
        };
        return classes[priority] || '';
    }

    updateUI() {
        // console.log('Updating UI with notifications:', this.notifications);
        if (this.badge) {
            const count = this.notifications.length;
            this.badge.textContent = count;
            this.badge.classList.toggle('hidden', count === 0);
            // console.log('Updated badge count:', count);
        }

        if (this.dropdown) {
            const content = this.notifications.map(notification => `
                <div class="flex items-center px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 ${this.getPriorityClass(notification.priority)}">
                    <div class="flex-shrink-0">
                        ${this.getNotificationIcon(notification.level)}
                    </div>
                    <div class="ml-3 flex-1">
                        <p class="text-sm text-gray-800 dark:text-gray-200">${notification.message}</p>
                        <div class="flex items-center mt-1">
                            <span class="text-xs text-gray-500 dark:text-gray-400">${this.formatDate(notification.createdAt)}</span>
                            ${notification.persistent ? '<span class="ml-2 text-xs text-blue-500">Persistent</span>' : ''}
                        </div>
                    </div>
                    ${!notification.persistent ? `
                        <button 
                            class="ml-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" 
                            data-notification-dismiss 
                            data-notification-id="${notification.id}"
                        >
                            <i class="fas fa-times"></i>
                        </button>
                    ` : ''}
                </div>
            `).join('');

            this.dropdown.innerHTML = `
                <div class="py-2 px-4 bg-gray-50 dark:bg-gray-700 border-b border-gray-100 dark:border-gray-600">
                    <div class="flex justify-between items-center">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-200">Notifications</h3>
                        <div class="flex space-x-2">
                            <button data-priority-filter data-priority="high" class="px-2 py-1 text-xs rounded bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">High</button>
                            <button data-priority-filter data-priority="medium" class="px-2 py-1 text-xs rounded bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">Medium</button>
                            <button data-priority-filter data-priority="low" class="px-2 py-1 text-xs rounded bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">Low</button>
                        </div>
                    </div>
                </div>
                ${content || '<div class="p-4 text-sm text-gray-500 dark:text-gray-400 text-center">No notifications</div>'}
                ${this.notifications.some(n => !n.persistent) ? `
                    <div class="py-2 px-4 bg-gray-50 dark:bg-gray-700 border-t border-gray-100 dark:border-gray-600">
                        <button id="clear-all-notifications" class="w-full text-sm text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">
                            Clear All Non-Persistent
                        </button>
                    </div>
                ` : ''}
            `;
            console.log('Updated dropdown content');
        }
    }

    getNotificationIcon(level) {
        const icons = {
            'info': '<i class="fas fa-info-circle text-blue-500"></i>',
            'warning': '<i class="fas fa-exclamation-triangle text-yellow-500"></i>',
            'error': '<i class="fas fa-exclamation-circle text-red-500"></i>',
            'success': '<i class="fas fa-check-circle text-green-500"></i>'
        };
        return icons[level] || icons.info;
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('fr-FR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM loaded, initializing NotificationManager');
    window.notificationManager = new NotificationManager();
});
