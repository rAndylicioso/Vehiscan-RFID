<header
    class="flex h-14 items-center gap-4 border-b border-gray-200 dark:border-slate-700 px-6 bg-white dark:bg-slate-900 transition-colors duration-300">
    <!-- Mobile Menu Button -->
    <button id="mobile-menu-btn"
        class="flex h-9 w-9 items-center justify-center rounded-md hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors md:hidden"
        aria-label="Toggle mobile menu">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
        </svg>
    </button>

    <h1 id="page-title" class="text-lg font-semibold text-gray-900 dark:text-white">Access Logs</h1>
    <div class="ml-auto flex items-center gap-4">
        <!-- New Logs Badge -->
        <div id="newLogsBadge"
            class="hidden bg-gray-700 text-white px-3 py-1.5 rounded-full text-sm font-semibold animate-pulse">
            <span class="badge-icon">🆕</span>
            <span id="newLogsCount">0</span> new
        </div>

        <!-- Live Time -->
        <div id="liveTime" class="text-sm font-mono text-gray-600 dark:text-gray-300 hidden sm:block">--:--:--</div>

        <!-- Guard Dark Mode Toggle -->
        <button id="guardDarkModeToggle" class="theme-toggle-btn" aria-label="Toggle Dark Mode">
            <svg class="theme-icon sun-icon h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z">
                </path>
            </svg>
            <svg class="theme-icon moon-icon h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
            </svg>
        </button>
    </div>
</header>