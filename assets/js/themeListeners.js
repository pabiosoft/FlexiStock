// Theme change listeners for dynamic UI updates
document.addEventListener('DOMContentLoaded', () => {
    // Listen for theme changes
    window.addEventListener('themeChanged', (e) => {
        const isDark = e.detail.isDark;
        
        // Update all UI components
        updateNavbarStyles(isDark);
        updateTableStyles(isDark);
        updateButtonStyles(isDark);
        updateCardStyles(isDark);
        updateFormStyles(isDark);
        updateModalStyles(isDark);
        updateSidebarStyles(isDark);
        updateStatusBadges(isDark);
    });
});

function updateNavbarStyles(isDark) {
    const navbar = document.querySelector('nav');
    if (navbar) {
        if (isDark) {
            navbar.classList.add('bg-gray-800', 'border-gray-700');
            navbar.classList.remove('bg-white', 'border-gray-200');
        } else {
            navbar.classList.add('bg-white', 'border-gray-200');
            navbar.classList.remove('bg-gray-800', 'border-gray-700');
        }
    }
}

function updateTableStyles(isDark) {
    // Update table containers
    const tableContainers = document.querySelectorAll('.table-container');
    tableContainers.forEach(container => {
        if (isDark) {
            container.classList.add('dark:bg-gray-800', 'dark:border-gray-700');
            container.classList.remove('bg-white', 'border-gray-200');
        } else {
            container.classList.add('bg-white', 'border-gray-200');
            container.classList.remove('dark:bg-gray-800', 'dark:border-gray-700');
        }
    });

    // Update table headers
    const tableHeaders = document.querySelectorAll('th');
    tableHeaders.forEach(header => {
        if (isDark) {
            header.classList.add('dark:bg-gray-700', 'dark:text-gray-200');
            header.classList.remove('bg-gray-50', 'text-gray-700');
        } else {
            header.classList.add('bg-gray-50', 'text-gray-700');
            header.classList.remove('dark:bg-gray-700', 'dark:text-gray-200');
        }
    });

    // Update table rows
    const tableRows = document.querySelectorAll('tr');
    tableRows.forEach(row => {
        if (isDark) {
            row.classList.add('dark:border-gray-700', 'dark:hover:bg-gray-700');
            row.classList.remove('border-gray-200', 'hover:bg-gray-50');
        } else {
            row.classList.add('border-gray-200', 'hover:bg-gray-50');
            row.classList.remove('dark:border-gray-700', 'dark:hover:bg-gray-700');
        }
    });
}

function updateButtonStyles(isDark) {
    // Primary buttons
    const primaryButtons = document.querySelectorAll('.btn-primary, .primary-button');
    primaryButtons.forEach(button => {
        if (isDark) {
            button.classList.add('dark:bg-blue-600', 'dark:hover:bg-blue-700', 'dark:text-white');
            button.classList.remove('bg-blue-500', 'hover:bg-blue-600', 'text-white');
        } else {
            button.classList.add('bg-blue-500', 'hover:bg-blue-600', 'text-white');
            button.classList.remove('dark:bg-blue-600', 'dark:hover:bg-blue-700', 'dark:text-white');
        }
    });

    // Secondary buttons
    const secondaryButtons = document.querySelectorAll('.btn-secondary, .secondary-button');
    secondaryButtons.forEach(button => {
        if (isDark) {
            button.classList.add('dark:bg-gray-600', 'dark:hover:bg-gray-700', 'dark:text-white');
            button.classList.remove('bg-gray-500', 'hover:bg-gray-600', 'text-white');
        } else {
            button.classList.add('bg-gray-500', 'hover:bg-gray-600', 'text-white');
            button.classList.remove('dark:bg-gray-600', 'dark:hover:bg-gray-700', 'dark:text-white');
        }
    });

    // Danger buttons
    const dangerButtons = document.querySelectorAll('.btn-danger, .danger-button');
    dangerButtons.forEach(button => {
        if (isDark) {
            button.classList.add('dark:bg-red-600', 'dark:hover:bg-red-700', 'dark:text-white');
            button.classList.remove('bg-red-500', 'hover:bg-red-600', 'text-white');
        } else {
            button.classList.add('bg-red-500', 'hover:bg-red-600', 'text-white');
            button.classList.remove('dark:bg-red-600', 'dark:hover:bg-red-700', 'dark:text-white');
        }
    });
}

function updateCardStyles(isDark) {
    const cards = document.querySelectorAll('.card, .stat-card');
    cards.forEach(card => {
        if (isDark) {
            card.classList.add('dark:bg-gray-800', 'dark:border-gray-700', 'dark:text-white');
            card.classList.remove('bg-white', 'border-gray-200', 'text-gray-800');
        } else {
            card.classList.add('bg-white', 'border-gray-200', 'text-gray-800');
            card.classList.remove('dark:bg-gray-800', 'dark:border-gray-700', 'dark:text-white');
        }
    });
}

function updateFormStyles(isDark) {
    // Input fields
    const inputs = document.querySelectorAll('input[type="text"], input[type="number"], input[type="email"], input[type="password"], textarea');
    inputs.forEach(input => {
        if (isDark) {
            input.classList.add('dark:bg-gray-700', 'dark:border-gray-600', 'dark:text-white');
            input.classList.remove('bg-white', 'border-gray-300', 'text-gray-900');
        } else {
            input.classList.add('bg-white', 'border-gray-300', 'text-gray-900');
            input.classList.remove('dark:bg-gray-700', 'dark:border-gray-600', 'dark:text-white');
        }
    });

    // Select fields
    const selects = document.querySelectorAll('select');
    selects.forEach(select => {
        if (isDark) {
            select.classList.add('dark:bg-gray-700', 'dark:border-gray-600', 'dark:text-white');
            select.classList.remove('bg-white', 'border-gray-300', 'text-gray-900');
        } else {
            select.classList.add('bg-white', 'border-gray-300', 'text-gray-900');
            select.classList.remove('dark:bg-gray-700', 'dark:border-gray-600', 'dark:text-white');
        }
    });

    // Labels
    const labels = document.querySelectorAll('label');
    labels.forEach(label => {
        if (isDark) {
            label.classList.add('dark:text-gray-200');
            label.classList.remove('text-gray-700');
        } else {
            label.classList.add('text-gray-700');
            label.classList.remove('dark:text-gray-200');
        }
    });
}

function updateModalStyles(isDark) {
    const modals = document.querySelectorAll('.modal, [role="dialog"]');
    modals.forEach(modal => {
        if (isDark) {
            modal.classList.add('dark:bg-gray-800', 'dark:text-white');
            modal.classList.remove('bg-white', 'text-gray-900');
        } else {
            modal.classList.add('bg-white', 'text-gray-900');
            modal.classList.remove('dark:bg-gray-800', 'dark:text-white');
        }
    });
}

function updateSidebarStyles(isDark) {
    const sidebar = document.querySelector('.sidebar');
    if (sidebar) {
        if (isDark) {
            sidebar.classList.add('dark:bg-gray-800', 'dark:text-white', 'dark:border-gray-700');
            sidebar.classList.remove('bg-white', 'text-gray-900', 'border-gray-200');
        } else {
            sidebar.classList.add('bg-white', 'text-gray-900', 'border-gray-200');
            sidebar.classList.remove('dark:bg-gray-800', 'dark:text-white', 'dark:border-gray-700');
        }
    }
}

function updateStatusBadges(isDark) {
    // Reserved badges
    const reservedBadges = document.querySelectorAll('.status-reserved');
    reservedBadges.forEach(badge => {
        if (isDark) {
            badge.classList.add('dark:bg-yellow-900', 'dark:text-yellow-200');
            badge.classList.remove('bg-yellow-100', 'text-yellow-800');
        } else {
            badge.classList.add('bg-yellow-100', 'text-yellow-800');
            badge.classList.remove('dark:bg-yellow-900', 'dark:text-yellow-200');
        }
    });

    // Completed badges
    const completedBadges = document.querySelectorAll('.status-completed');
    completedBadges.forEach(badge => {
        if (isDark) {
            badge.classList.add('dark:bg-green-900', 'dark:text-green-200');
            badge.classList.remove('bg-green-100', 'text-green-800');
        } else {
            badge.classList.add('bg-green-100', 'text-green-800');
            badge.classList.remove('dark:bg-green-900', 'dark:text-green-200');
        }
    });

    // Cancelled badges
    const cancelledBadges = document.querySelectorAll('.status-cancelled');
    cancelledBadges.forEach(badge => {
        if (isDark) {
            badge.classList.add('dark:bg-red-900', 'dark:text-red-200');
            badge.classList.remove('bg-red-100', 'text-red-800');
        } else {
            badge.classList.add('bg-red-100', 'text-red-800');
            badge.classList.remove('dark:bg-red-900', 'dark:text-red-200');
        }
    });
}
