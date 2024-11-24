// Theme Switcher functionality
export const initializeThemeSwitcher = () => {
    const themeToggleBtn = document.getElementById('theme-toggle');
    const htmlElement = document.documentElement;

    // Check for saved theme preference or use system preference
    const savedTheme = localStorage.getItem('theme') || 
        (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    
    // Apply saved theme on load
    if (savedTheme === 'dark') {
        htmlElement.classList.add('dark');
    }

    // Update button icon based on current theme
    const updateButtonIcon = (isDark) => {
        const sunIcon = themeToggleBtn.querySelector('.sun-icon');
        const moonIcon = themeToggleBtn.querySelector('.moon-icon');
        sunIcon.classList.toggle('hidden', isDark);
        moonIcon.classList.toggle('hidden', !isDark);
    };

    updateButtonIcon(savedTheme === 'dark');

    // Handle theme toggle click
    themeToggleBtn?.addEventListener('click', () => {
        const isDark = htmlElement.classList.toggle('dark');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
        updateButtonIcon(isDark);
    });

    // Listen for system theme changes
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
        if (!localStorage.getItem('theme')) {
            const shouldBeDark = e.matches;
            htmlElement.classList.toggle('dark', shouldBeDark);
            updateButtonIcon(shouldBeDark);
        }
    });
};