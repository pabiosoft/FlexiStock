import './styles/app.css';
import { initializeTheme } from './js/theme';
import './js/themeListeners';

// Import notifications
import './js/notifications';

import 'flowbite';

// Initialize theme functionality
document.addEventListener('DOMContentLoaded', () => {
    initializeTheme();
});