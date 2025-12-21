/**
 * Breadcrumb Navigation System
 * Provides dynamic breadcrumb updates for SPA-style navigation
 * Location: assets/js/breadcrumb.js
 * Phase 3.2
 */

(function () {
    'use strict';

    // Breadcrumb state
    let currentBreadcrumbs = [];

    // Update breadcrumb display
    const renderBreadcrumbs = () => {
        const container = document.getElementById('breadcrumb-container');
        if (!container) return;

        if (currentBreadcrumbs.length === 0) {
            container.innerHTML = '';
            return;
        }

        const items = currentBreadcrumbs.map((crumb, index) => {
            const isLast = index === currentBreadcrumbs.length - 1;

            if (isLast) {
                return `<span class="breadcrumb-item active" aria-current="page">${crumb.label}</span>`;
            } else {
                const clickAttr = crumb.onClick ? `onclick="${crumb.onClick}"` : '';
                const href = crumb.href || '#';
                return `
          <a href="${href}" class="breadcrumb-item" ${clickAttr}>
            ${crumb.label}
          </a>
          <span class="breadcrumb-separator">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
          </span>
        `;
            }
        }).join('');

        container.innerHTML = `<nav class="breadcrumb" aria-label="Breadcrumb">${items}</nav>`;
    };

    // Public API
    window.breadcrumb = {
        // Set breadcrumbs array
        set: function (breadcrumbs) {
            currentBreadcrumbs = breadcrumbs;
            renderBreadcrumbs();
        },

        // Update from page navigation
        updateFromPage: function (pageName) {
            const breadcrumbMap = {
                // Admin
                'dashboard': [{ label: 'Dashboard' }],
                'manage': [{ label: 'Dashboard', onClick: 'loadPage("dashboard")' }, { label: 'Manage Records' }],
                'logs': [{ label: 'Dashboard', onClick: 'loadPage("dashboard")' }, { label: 'Access Logs' }],
                'reports': [{ label: 'Dashboard', onClick: 'loadPage("dashboard")' }, { label: 'Reports' }],

                // Guard
                'access-logs': [{ label: 'Access Logs' }],
                'homeowners': [{ label: 'Access Logs', onClick: 'loadPage("logs")' }, { label: 'Homeowners' }],
                'camera': [{ label: 'Access Logs', onClick: 'loadPage("logs")' }, { label: 'Camera Feed' }],

                // Homeowner
                'passes': [{ label: 'Dashboard', onClick: 'loadPage("dashboard")' }, { label: 'Visitor Passes' }],
                'vehicles': [{ label: 'Dashboard', onClick: 'loadPage("dashboard")' }, { label: 'My Vehicles' }],
                'activity': [{ label: 'Dashboard', onClick: 'loadPage("dashboard")' }, { label: 'Vehicle Activity' }],
                'profile': [{ label: 'Dashboard', onClick: 'loadPage("dashboard")' }, { label: 'Profile' }]
            };

            const breadcrumbs = breadcrumbMap[pageName] || [{ label: pageName }];
            this.set(breadcrumbs);
        },

        // Clear breadcrumbs
        clear: function () {
            currentBreadcrumbs = [];
            renderBreadcrumbs();
        }
    };

    console.log('[Breadcrumb] Breadcrumb navigation system initialized (Phase 3.2)');

})();
