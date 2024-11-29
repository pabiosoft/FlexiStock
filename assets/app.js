import './styles/app.css';
import { initializeTheme } from './js/theme';
import './js/themeListeners';

import 'flowbite';

// Initialize theme functionality
document.addEventListener('DOMContentLoaded', () => {
    initializeTheme();
});