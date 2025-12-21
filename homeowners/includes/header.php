<!-- Header -->
<header
    class="flex h-14 items-center gap-4 border-b border-gray-200 px-6 bg-white shadow-sm dark:bg-slate-900 dark:border-slate-800 transition-colors duration-300">
    <!-- Mobile Menu Button -->
    <button id="mobile-menu-btn"
        class="flex items-center justify-center h-10 w-10 rounded-lg hover:bg-gray-100 active:bg-gray-200 transition-all duration-200">
        <svg class="h-5 w-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
        </svg>
    </button>

    <div class="flex items-center gap-3">
        <div class="h-8 w-1 bg-blue-600 rounded-full"></div>
        <h1 id="page-title" class="text-xl font-bold text-gray-900 transition-all duration-300 dark:text-white">
            Dashboard
        </h1>
    </div>

    <div class="ml-auto flex items-center gap-4">
        <!-- Dark Mode Toggle -->
        <button id="homeownerDarkModeToggle" class="theme-toggle-btn" aria-label="Toggle Dark Mode">
            <svg class="theme-icon sun-icon h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z">
                </path>
            </svg>
            <svg class="theme-icon moon-icon h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z">
                </path>
            </svg>
        </button>
        <!-- Live Clock -->
        <div
            class="hidden sm:flex items-center gap-2 px-4 py-2 bg-gray-50 rounded-lg border border-gray-200 dark:bg-slate-800 dark:border-slate-700">
            <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span id="liveTime" class="text-gray-700 text-sm font-semibold tabular-nums dark:text-gray-300"></span>
        </div>

        <!-- Session indicator -->
        <div class="hidden md:flex items-center gap-2 px-3 py-1.5 bg-green-50 border border-green-200 rounded-lg">
            <div class="h-2 w-2 bg-green-500 rounded-full animate-pulse"></div>
            <span class="text-xs font-semibold text-green-700">Active</span>
        </div>
    </div>
</header>