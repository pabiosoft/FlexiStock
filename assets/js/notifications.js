// Test if the file is loaded
// console.log('Notifications.js is loaded');

class NotificationManager {
    constructor() {
        // console.log('NotificationManager constructor called');
        this.initializeElements();
        if (this.hasRequiredElements()) {
            this.notifications = [];
            this.currentPage = 1;
            this.totalPages = 1;
            this.itemsPerPage = 3; // Updated to match backend limit
            this.init();
        } else {
            console.warn('Missing required elements for NotificationManager');
            if (!this.container) {
                this.container = document.createElement('div');
                this.container.id = 'notification-container';
                this.container.className = 'fixed top-4 right-4 z-50 space-y-2 w-96';
                document.body.appendChild(this.container);
            }
        }
    }

    initializeElements() {
        this.container = document.getElementById('notification-container');
        this.badge = document.querySelector('.notification-badge');
        this.dropdown = document.getElementById('notification-dropdown');
        this.toggleButton = document.getElementById('notification-toggle');
    }

    hasRequiredElements() {
        const hasElements = this.toggleButton && this.dropdown;
        return hasElements;
    }

    init() {
        this.fetchNotifications();
        setInterval(() => this.fetchNotifications(), 30000);

        if (this.toggleButton && this.dropdown) {
            this.toggleButton.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.toggleDropdown();
            });

            document.addEventListener('click', (e) => {
                if (this.dropdown && !this.dropdown.contains(e.target) && !this.toggleButton.contains(e.target)) {
                    this.dropdown.classList.add('hidden');
                }
            });

            this.dropdown.addEventListener('click', (e) => {
                const dismissButton = e.target.closest('[data-notification-dismiss], .mark-read-btn');
                const clearAllButton = e.target.closest('#clear-all-notifications');
                const priorityFilter = e.target.closest('[data-priority-filter]');

                if (dismissButton) {
                    e.preventDefault();
                    const notificationId = dismissButton.dataset.notificationId || dismissButton.dataset.alertId;
                    this.markAsRead(notificationId);
                }
                if (clearAllButton) {
                    e.preventDefault();
                    this.clearAllNotifications();
                }
                if (priorityFilter) {
                    e.preventDefault();
                    const priority = priorityFilter.dataset.priority;
                    this.fetchNotificationsByPriority(priority);
                }
            });
        }
    }

    toggleDropdown() {
        if (this.dropdown) {
            const isHidden = this.dropdown.classList.contains('hidden');
            if (isHidden) {
                // Position the dropdown relative to the toggle button
                const buttonRect = this.toggleButton.getBoundingClientRect();
                this.dropdown.style.position = 'fixed';
                this.dropdown.style.top = `${buttonRect.bottom + window.scrollY}px`;
                this.dropdown.style.right = `${window.innerWidth - buttonRect.right}px`;
                
                // Fetch latest notifications when opening
                this.fetchNotifications();
            }
            this.dropdown.classList.toggle('hidden');
        }
    }

    async fetchNotifications() {
        try {
            const params = new URLSearchParams({
                page: this.currentPage,
                limit: this.itemsPerPage
            });
            const response = await fetch(`/notifications/fetch?${params.toString()}`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();
            
            if (data.success) {
                this.notifications = data.data.notifications;
                this.totalPages = data.data.pagination.totalPages;
                this.currentPage = data.data.pagination.currentPage;
                this.totalItems = data.data.pagination.totalItems;
                
                // Only update UI if dropdown is visible
                if (!this.dropdown.classList.contains('hidden')) {
                    this.updateUI();
                }
                
                // Always update badge
                if (this.badge) {
                    const count = this.totalItems || 0;
                    this.badge.textContent = count;
                    this.badge.classList.toggle('hidden', count === 0);
                }
            }
        } catch (error) {
            console.error('Error fetching notifications:', error);
        }
    }

    async fetchNotificationsByPriority(priority) {
        try {
            const response = await fetch(`/notifications/priority/${priority}`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();
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
            const response = await fetch(`/notifications/${notificationId}/mark-read`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();
            this.notifications = this.notifications.filter(n => n.id !== parseInt(notificationId));
            this.updateUI();
        } catch (error) {
            console.error('Error marking notification as read:', error);
        }
    }

    async clearAllNotifications() {
        try {
            const response = await fetch('/notifications/clear-all', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();
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
        if (this.badge) {
            const count = this.totalItems || 0;
            this.badge.textContent = count;
            this.badge.classList.toggle('hidden', count === 0);
        }

        if (this.dropdown) {
            // Group notifications by category
            const groupedNotifications = this.notifications.reduce((acc, notification) => {
                const category = notification.category || 'Other';
                if (!acc[category]) acc[category] = [];
                acc[category].push(notification);
                return acc;
            }, {});

            let content = '';
            
            if (Object.keys(groupedNotifications).length === 0) {
                content = '<div class="p-4 text-sm text-gray-500 dark:text-gray-400 text-center">No notifications</div>';
            } else {
                // Generate content for each category
                Object.entries(groupedNotifications).forEach(([category, notifications]) => {
                    content += `
                        <div class="category-section">
                            <div class="px-4 py-2 bg-gray-50 dark:bg-gray-700 border-b border-gray-100 dark:border-gray-600">
                                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    ${this.getCategoryIcon(category)} ${category}
                                </h4>
                            </div>
                            ${notifications.map(notification => `
                                <div class="flex items-center px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 ${this.getPriorityClass(notification.priority)} group">
                                    <div class="flex-shrink-0">
                                        ${this.getNotificationIcon(notification.level)}
                                    </div>
                                    <div class="ml-3 flex-1">
                                        <p class="text-sm text-gray-800 dark:text-gray-200">${notification.message}</p>
                                        <div class="flex items-center mt-1">
                                            <span class="text-xs text-gray-500 dark:text-gray-400">${this.formatDate(notification.createdAt)}</span>
                                            ${notification.persistent ? '<span class="ml-2 text-xs text-blue-500">Persistent</span>' : ''}
                                            <span class="ml-2 text-xs ${this.getPriorityTextClass(notification.priority)}">${notification.priority}</span>
                                        </div>
                                    </div>
                                    ${!notification.persistent ? `
                                        <button 
                                            class="ml-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 opacity-0 group-hover:opacity-100 transition-opacity" 
                                            data-notification-dismiss 
                                            data-notification-id="${notification.id}"
                                            title="Mark as read"
                                        >
                                            <i class="fas fa-check"></i>
                                        </button>
                                    ` : ''}
                                </div>
                            `).join('')}
                        </div>
                    `;
                });
            }

            this.dropdown.innerHTML = `
                <div class="py-2 px-4 bg-gray-50 dark:bg-gray-700 border-b border-gray-100 dark:border-gray-600">
                    <div class="flex justify-between items-center">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-200">
                            Notifications 
                            ${this.totalItems > 0 ? `<span class="text-gray-500 dark:text-gray-400">(${this.totalItems})</span>` : ''}
                        </h3>
                        <div class="flex space-x-2">
                            <button data-priority-filter data-priority="high" class="px-2 py-1 text-xs rounded bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 hover:bg-red-200 dark:hover:bg-red-800 transition-colors">High</button>
                            <button data-priority-filter data-priority="medium" class="px-2 py-1 text-xs rounded bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 hover:bg-yellow-200 dark:hover:bg-yellow-800 transition-colors">Medium</button>
                            <button data-priority-filter data-priority="low" class="px-2 py-1 text-xs rounded bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 hover:bg-blue-200 dark:hover:bg-blue-800 transition-colors">Low</button>
                        </div>
                    </div>
                </div>
                ${content}
                ${this.totalItems > 0 ? `
                    <div class="py-2 px-4 bg-gray-50 dark:bg-gray-700 border-t border-gray-100 dark:border-gray-600">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <button 
                                    class="px-2 py-1 text-sm text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                                    ${this.currentPage <= 1 ? 'disabled' : ''}
                                    onclick="window.notificationManager.previousPage()"
                                >
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                                <span class="text-sm text-gray-600 dark:text-gray-400">
                                    ${this.currentPage} / ${this.totalPages}
                                </span>
                                <button 
                                    class="px-2 py-1 text-sm text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                                    ${this.currentPage >= this.totalPages ? 'disabled' : ''}
                                    onclick="window.notificationManager.nextPage()"
                                >
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                            </div>
                            ${this.notifications.some(n => !n.persistent) ? `
                                <button id="clear-all-notifications" class="text-sm text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
                                    <i class="fas fa-check-double mr-1"></i> Mark All as Read
                                </button>
                            ` : ''}
                        </div>
                    </div>
                ` : ''}
            `;
        }
    }

    getCategoryIcon(category) {
        const icons = {
            'Maintenance': '<i class="fas fa-tools text-gray-500 dark:text-gray-400"></i>',
            'Warranty': '<i class="fas fa-shield-alt text-gray-500 dark:text-gray-400"></i>',
            'Equipment': '<i class="fas fa-cogs text-gray-500 dark:text-gray-400"></i>',
            'Stock': '<i class="fas fa-box text-gray-500 dark:text-gray-400"></i>',
            'Other': '<i class="fas fa-bell text-gray-500 dark:text-gray-400"></i>'
        };
        return icons[category] || icons.Other;
    }

    getPriorityTextClass(priority) {
        const classes = {
            'high': 'text-red-600 dark:text-red-400',
            'medium': 'text-yellow-600 dark:text-yellow-400',
            'low': 'text-blue-600 dark:text-blue-400'
        };
        return classes[priority] || '';
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

    nextPage() {
        if (this.currentPage < this.totalPages) {
            this.currentPage++;
            this.fetchNotifications();
        }
    }

    previousPage() {
        if (this.currentPage > 1) {
            this.currentPage--;
            this.fetchNotifications();
        }
    }

    showNewNotifications() {
        if (!this.container) return;
        
        const newNotifications = this.notifications.filter(n => !n.displayed);
        
        newNotifications.forEach(notification => {
            notification.displayed = true;
            
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
            
            this.container.appendChild(notifElement);
            
            setTimeout(() => {
                notifElement.classList.remove('opacity-0', 'translate-x-full');
            }, 100);
            
            setTimeout(() => {
                notifElement.classList.add('opacity-0', 'translate-x-full');
                setTimeout(() => {
                    notifElement.remove();
                }, 300);
            }, 5000);
        });
    }
}

document.addEventListener('DOMContentLoaded', () => {
    // console.log('DOM loaded, initializing NotificationManager');
    window.notificationManager = new NotificationManager();
});
