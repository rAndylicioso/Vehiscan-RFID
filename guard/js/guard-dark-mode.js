/**
 * Guard Panel Dark Mode
 * Dedicated dark mode system for guard panel
 */

(function () {
  'use strict';

  const darkModeToggle = document.getElementById('guardDarkModeToggle');
  const html = document.documentElement;
  const body = document.body;

  // Check for saved theme preference or default to light
  const currentTheme = localStorage.getItem('guardDarkMode') || 'light';

  // Initialize theme
  function enableDarkMode() {
    html.classList.add('dark');
    body.classList.add('dark', 'bg-slate-900', 'text-white');
    body.classList.remove('bg-gray-50', 'text-gray-900');
  }

  function enableLightMode() {
    html.classList.remove('dark');
    body.classList.remove('dark', 'bg-slate-900', 'text-white');
    body.classList.add('bg-gray-50', 'text-gray-900');
  }

  // Initialize on load
  if (currentTheme === 'dark') {
    enableDarkMode();
  } else {
    enableLightMode();
  }

  // Toggle dark mode
  if (darkModeToggle) {
    darkModeToggle.addEventListener('click', () => {
      const isDark = html.classList.contains('dark');

      if (isDark) {
        enableLightMode();
        localStorage.setItem('guardDarkMode', 'light');
      } else {
        enableDarkMode();
        localStorage.setItem('guardDarkMode', 'dark');
      }
    });

    // Keyboard accessibility
    darkModeToggle.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        darkModeToggle.click();
      }
    });
  }
})();
