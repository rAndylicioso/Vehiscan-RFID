/**
 * Homeowner Portal Dark Mode
 * Matches system-wide dark mode implementation
 */

(function () {
    'use strict';

    const toggleBtn = document.getElementById('homeownerDarkModeToggle');
    const html = document.documentElement;
    const body = document.body;

    // Check for saved theme preference or default to light
    const currentTheme = localStorage.getItem('homeownerDarkMode') || 'light';

    // Initialize theme
    function enableDarkMode() {
        html.classList.add('dark');
        // Homeowner side unfortunately has hardcoded backgrounds in some places or uses different structure.
        // We enforce the dark classes on body to cascade.
        body.classList.add('dark', 'bg-slate-900', 'text-white');
        body.classList.remove('bg-[#F5F5F5]', 'text-gray-900');
        // Also target the inline style if possible, or override via class
        body.style.backgroundColor = ''; // Remove inline style
    }

    function enableLightMode() {
        html.classList.remove('dark');
        body.classList.remove('dark', 'bg-slate-900', 'text-white');
        body.classList.add('text-gray-900');
        body.style.backgroundColor = '#F5F5F5'; // Restore default light bg
    }

    // Initialize on load
    if (currentTheme === 'dark') {
        enableDarkMode();
    } else {
        enableLightMode();
    }

    // Toggle listener
    if (toggleBtn) {
        toggleBtn.addEventListener('click', () => {
            const isDark = html.classList.contains('dark');

            if (isDark) {
                enableLightMode();
                localStorage.setItem('homeownerDarkMode', 'light');
            } else {
                enableDarkMode();
                localStorage.setItem('homeownerDarkMode', 'dark');
            }
        });

        // Keyboard accessibility
        toggleBtn.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                toggleBtn.click();
            }
        });
    }
})();
