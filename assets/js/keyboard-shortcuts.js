/**
 * Keyboard Shortcuts System
 * Provides global keyboard shortcuts with conflict prevention
 * Location: assets/js/keyboard-shortcuts.js
 * Phase 3.3
 */

(function () {
    'use strict';

    // Registered shortcuts
    const shortcuts = new Map();
    let enabled = true;

    // Check if user is typing in an input
    const isTyping = () => {
        const activeEl = document.activeElement;
        return activeEl && (
            activeEl.tagName === 'INPUT' ||
            activeEl.tagName === 'TEXTAREA' ||
            activeEl.isContentEditable
        );
    };

    // Handle keydown events
    const handleKeydown = (e) => {
        if (!enabled) return;

        // Build key combination string
        const parts = [];
        if (e.ctrlKey || e.metaKey) parts.push('ctrl');
        if (e.altKey) parts.push('alt');
        if (e.shiftKey) parts.push('shift');
        parts.push(e.key.toLowerCase());
        const combo = parts.join('+');

        // Check if shortcut is registered
        const shortcut = shortcuts.get(combo);
        if (!shortcut) return;

        // Check if typing (unless shortcut allows it)
        if (isTyping() && !shortcut.allowWhileTyping) return;

        // Prevent default if specified
        if (shortcut.preventDefault) {
            e.preventDefault();
        }

        // Execute callback
        shortcut.callback(e);
    };

    // Show help modal
    const showHelp = () => {
        const shortcutList = Array.from(shortcuts.entries())
            .map(([combo, data]) => {
                const keys = combo.split('+').map(k => {
                    const keyMap = {
                        'ctrl': 'Ctrl',
                        'alt': 'Alt',
                        'shift': 'Shift'
                    };
                    return keyMap[k] || k.toUpperCase();
                }).join(' + ');

                return `
          <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-700">
            <span class="text-sm text-gray-700 dark:text-gray-300">${data.description}</span>
            <kbd class="px-2 py-1 text-xs font-semibold text-gray-800 bg-gray-100 border border-gray-200 rounded dark:bg-gray-700 dark:text-gray-100 dark:border-gray-600">
              ${keys}
            </kbd>
          </div>
        `;
            }).join('');

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Keyboard Shortcuts',
                html: `<div class="text-left max-h-96 overflow-y-auto">${shortcutList}</div>`,
                icon: 'info',
                confirmButtonText: 'Got it!',
                confirmButtonColor: '#3b82f6',
                width: '600px'
            });
        }
    };

    // Public API
    window.keyboardShortcuts = {
        // Register a shortcut
        register: function (key, callback, options = {}) {
            const {
                description = 'No description',
                preventDefault = true,
                allowWhileTyping = false
            } = options;

            shortcuts.set(key.toLowerCase(), {
                callback,
                description,
                preventDefault,
                allowWhileTyping
            });
        },

        // Unregister a shortcut
        unregister: function (key) {
            shortcuts.delete(key.toLowerCase());
        },

        // Show help modal
        showHelp: showHelp,

        // Enable/disable shortcuts
        enable: function () {
            enabled = true;
        },

        disable: function () {
            enabled = false;
        },

        // Get all registered shortcuts
        getAll: function () {
            return Array.from(shortcuts.keys());
        }
    };

    // Register global shortcuts
    const registerGlobalShortcuts = () => {
        // Help modal
        window.keyboardShortcuts.register('?', showHelp, {
            description: 'Show keyboard shortcuts',
            preventDefault: true,
            allowWhileTyping: false
        });

        // Focus search (if exists)
        window.keyboardShortcuts.register('ctrl+k', () => {
            const searchInputs = [
                document.getElementById('logsSearch'),
                document.getElementById('searchInput'),
                document.querySelector('input[type="search"]'),
                document.querySelector('input[placeholder*="Search"]')
            ];

            const searchInput = searchInputs.find(el => el && el.offsetParent !== null);
            if (searchInput) {
                searchInput.focus();
                searchInput.select();
            }
        }, {
            description: 'Focus search',
            preventDefault: true
        });

        // Close modals with Escape (enhance existing behavior)
        window.keyboardShortcuts.register('escape', () => {
            // Close SweetAlert
            if (typeof Swal !== 'undefined' && Swal.isVisible()) {
                Swal.close();
            }

            // Close custom modals
            const modals = document.querySelectorAll('.modal:not(.hidden), [id*="Modal"]:not(.hidden)');
            modals.forEach(modal => {
                if (modal.classList.contains('hidden') === false) {
                    modal.classList.add('hidden');
                }
            });
        }, {
            description: 'Close modals/dialogs',
            preventDefault: false,
            allowWhileTyping: true
        });

        // Navigation shortcuts (portal-specific, will be overridden if needed)
        window.keyboardShortcuts.register('ctrl+h', () => {
            if (typeof loadPage === 'function') {
                loadPage('dashboard');
            } else if (window.location.pathname.includes('admin')) {
                window.location.href = '/Vehiscan-RFID/admin/admin_panel.php';
            } else if (window.location.pathname.includes('guard')) {
                window.location.href = '/Vehiscan-RFID/guard/pages/guard_side.php';
            } else if (window.location.pathname.includes('homeowners')) {
                window.location.href = '/Vehiscan-RFID/homeowners/portal.php';
            }
        }, {
            description: 'Go to Dashboard/Home',
            preventDefault: true
        });

        window.keyboardShortcuts.register('ctrl+l', () => {
            if (typeof loadPage === 'function') {
                loadPage('logs');
            }
        }, {
            description: 'View Access Logs',
            preventDefault: true
        });

        window.keyboardShortcuts.register('ctrl+v', () => {
            if (typeof loadPage === 'function') {
                loadPage('visitor-passes');
            }
        }, {
            description: 'View Visitor Passes',
            preventDefault: true
        });

        window.keyboardShortcuts.register('ctrl+m', () => {
            if (typeof loadPage === 'function') {
                loadPage('manage');
            }
        }, {
            description: 'Manage Records',
            preventDefault: true
        });

        // Action shortcuts
        window.keyboardShortcuts.register('ctrl+n', () => {
            // Try to find and click "Add New" or similar button
            const addButtons = [
                document.querySelector('[onclick*="openAddModal"]'),
                document.querySelector('[onclick*="openNewModal"]'),
                document.querySelector('button[data-action="add"]'),
                document.querySelector('.btn-add')
            ];

            const addButton = addButtons.find(btn => btn && btn.offsetParent !== null);
            if (addButton) {
                addButton.click();
            }
        }, {
            description: 'Add New Record',
            preventDefault: true
        });

        // Focus search with /
        window.keyboardShortcuts.register('/', () => {
            const searchInputs = [
                document.getElementById('logsSearch'),
                document.getElementById('searchInput'),
                document.querySelector('input[type="search"]'),
                document.querySelector('input[placeholder*="Search"]')
            ];

            const searchInput = searchInputs.find(el => el && el.offsetParent !== null);
            if (searchInput) {
                searchInput.focus();
                searchInput.select();
            }
        }, {
            description: 'Focus Search',
            preventDefault: true,
            allowWhileTyping: false
        });
    };

    // Initialize
    document.addEventListener('keydown', handleKeydown);
    registerGlobalShortcuts();

    console.log('[Keyboard] Keyboard shortcuts system initialized (Phase 3.3)');
    console.log('[Keyboard] Press ? to see all available shortcuts');

})();
