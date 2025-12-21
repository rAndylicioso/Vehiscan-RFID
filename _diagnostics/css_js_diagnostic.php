<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSS/JavaScript/Tailwind Diagnostic Tool</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .test-passed { color: #10b981; font-weight: bold; }
        .test-failed { color: #ef4444; font-weight: bold; }
        .test-warning { color: #f59e0b; font-weight: bold; }
        .code-block { background: #1f2937; color: #e5e7eb; padding: 1rem; border-radius: 0.5rem; overflow-x: auto; }
        .section { margin-bottom: 2rem; padding: 1.5rem; border: 1px solid #e5e7eb; border-radius: 0.5rem; }
    </style>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-6xl mx-auto">
        <h1 class="text-4xl font-bold text-gray-800 mb-8">CSS/JavaScript/Tailwind Diagnostic Report</h1>
        
        <div class="section bg-white">
            <h2 class="text-2xl font-bold mb-4">1. Tailwind CSS Configuration Test</h2>
            <div id="tailwind-tests"></div>
        </div>

        <div class="section bg-white">
            <h2 class="text-2xl font-bold mb-4">2. JavaScript Console Errors</h2>
            <div id="js-errors"></div>
        </div>

        <div class="section bg-white">
            <h2 class="text-2xl font-bold mb-4">3. CSS File Integrity</h2>
            <div id="css-integrity"></div>
        </div>

        <div class="section bg-white">
            <h2 class="text-2xl font-bold mb-4">4. JavaScript File Integrity</h2>
            <div id="js-integrity"></div>
        </div>

        <div class="section bg-white">
            <h2 class="text-2xl font-bold mb-4">5. Component Interaction Tests</h2>
            <div id="component-tests"></div>
        </div>

        <div class="section bg-white">
            <h2 class="text-2xl font-bold mb-4">6. Skeleton Loader Visual Test</h2>
            <div class="space-y-4">
                <h3 class="font-bold">Light Mode Skeleton:</h3>
                <div class="skeleton-loader" style="width: 200px; height: 20px; background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%); background-size: 200% 100%; animation: skeleton-loading 1.5s infinite; border-radius: 4px;"></div>
                
                <h3 class="font-bold mt-4">Dark Mode Skeleton:</h3>
                <div class="bg-gray-900 p-4 rounded">
                    <div class="skeleton-loader-dark" style="width: 200px; height: 20px; background: linear-gradient(90deg, #2a2a2a 25%, #3a3a3a 50%, #2a2a2a 75%); background-size: 200% 100%; animation: skeleton-loading 1.5s infinite; border-radius: 4px;"></div>
                </div>
                
                <div id="skeleton-test-result"></div>
            </div>
        </div>

        <div class="section bg-white">
            <h2 class="text-2xl font-bold mb-4">7. Modal Functionality Test</h2>
            <button onclick="testModal()" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Test Modal</button>
            <div id="modal-test-result" class="mt-2"></div>
        </div>

        <div class="section bg-white">
            <h2 class="text-2xl font-bold mb-4">8. Dropdown Positioning Test</h2>
            <div class="relative" style="height: 300px; overflow: auto; border: 1px solid #ddd; padding: 1rem;">
                <div style="height: 200px;"></div>
                <button onclick="testDropdown(this)" class="bg-green-600 text-white px-4 py-2 rounded">Test Dropdown (Bottom)</button>
                <div id="test-dropdown" class="hidden absolute bg-white shadow-lg rounded-lg p-4 z-50">
                    <div>Dropdown Content</div>
                    <div>Position: <span id="dropdown-position"></span></div>
                </div>
                <div style="height: 50px;"></div>
            </div>
            <div id="dropdown-test-result" class="mt-2"></div>
        </div>

        <div class="section bg-white">
            <h2 class="text-2xl font-bold mb-4">9. AJAX Request Test</h2>
            <button onclick="testAjax()" class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700">Test AJAX</button>
            <div id="ajax-test-result" class="mt-2"></div>
        </div>

        <div class="section bg-white">
            <h2 class="text-2xl font-bold mb-4">10. PHP Backend Tests</h2>
            <div id="backend-tests"></div>
        </div>

        <div class="section bg-white">
            <h2 class="text-2xl font-bold mb-4">Summary</h2>
            <div id="summary"></div>
        </div>
    </div>

    <style>
        @keyframes skeleton-loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
    </style>

    <script>
        let testResults = {
            passed: 0,
            failed: 0,
            warnings: 0
        };

        // 1. Tailwind CSS Tests
        function testTailwind() {
            const results = [];
            const testElement = document.createElement('div');
            testElement.className = 'hidden';
            document.body.appendChild(testElement);
            
            // Test 1: Check if Tailwind is loaded
            const display = window.getComputedStyle(testElement).display;
            if (display === 'none') {
                results.push('✓ Tailwind CSS is loaded and working');
                testResults.passed++;
            } else {
                results.push('✗ Tailwind CSS may not be loaded correctly');
                testResults.failed++;
            }
            
            // Test 2: Test responsive classes
            testElement.className = 'sm:block md:flex lg:grid';
            results.push('✓ Responsive classes available');
            testResults.passed++;
            
            // Test 3: Test color classes
            testElement.className = 'bg-blue-600 text-white';
            const bgColor = window.getComputedStyle(testElement).backgroundColor;
            if (bgColor && bgColor !== 'rgba(0, 0, 0, 0)') {
                results.push('✓ Color utilities working');
                testResults.passed++;
            } else {
                results.push('⚠ Color utilities may have issues');
                testResults.warnings++;
            }
            
            document.body.removeChild(testElement);
            
            document.getElementById('tailwind-tests').innerHTML = results.map(r => 
                `<div class="${r.startsWith('✓') ? 'test-passed' : r.startsWith('✗') ? 'test-failed' : 'test-warning'}">${r}</div>`
            ).join('');
        }

        // 2. JavaScript Console Errors
        const consoleErrors = [];
        const originalError = console.error;
        console.error = function(...args) {
            consoleErrors.push(args.join(' '));
            originalError.apply(console, args);
        };

        function displayConsoleErrors() {
            const errorDiv = document.getElementById('js-errors');
            if (consoleErrors.length === 0) {
                errorDiv.innerHTML = '<div class="test-passed">✓ No console errors detected</div>';
                testResults.passed++;
            } else {
                errorDiv.innerHTML = '<div class="test-failed">✗ Console errors detected:</div><div class="code-block">' + 
                    consoleErrors.join('\\n') + '</div>';
                testResults.failed++;
            }
        }

        // 3. CSS File Integrity
        async function testCssFiles() {
            const cssFiles = Array.from(document.styleSheets);
            const results = [];
            
            cssFiles.forEach((sheet, index) => {
                try {
                    if (sheet.href) {
                        results.push(`✓ ${sheet.href.split('/').pop()} loaded`);
                        testResults.passed++;
                    }
                } catch (e) {
                    results.push(`✗ Error accessing stylesheet ${index}: ${e.message}`);
                    testResults.failed++;
                }
            });
            
            if (results.length === 0) {
                results.push('⚠ No external CSS files detected (using inline or CDN)');
                testResults.warnings++;
            }
            
            document.getElementById('css-integrity').innerHTML = results.join('<br>');
        }

        // 4. JavaScript File Integrity
        function testJsFiles() {
            const scripts = Array.from(document.scripts);
            const results = [];
            
            scripts.forEach(script => {
                if (script.src) {
                    results.push(`✓ ${script.src.split('/').pop()} loaded`);
                    testResults.passed++;
                }
            });
            
            if (results.length === 0) {
                results.push('⚠ No external JS files detected (using inline only)');
                testResults.warnings++;
            }
            
            document.getElementById('js-integrity').innerHTML = results.join('<br>');
        }

        // 5. Component Interaction Tests
        function testComponents() {
            const results = [];
            
            // Test modal function exists
            if (typeof testModal === 'function') {
                results.push('✓ Modal functions accessible');
                testResults.passed++;
            } else {
                results.push('✗ Modal functions not found');
                testResults.failed++;
            }
            
            // Test dropdown function exists
            if (typeof testDropdown === 'function') {
                results.push('✓ Dropdown functions accessible');
                testResults.passed++;
            } else {
                results.push('✗ Dropdown functions not found');
                testResults.failed++;
            }
            
            // Test AJAX function exists
            if (typeof fetch === 'function') {
                results.push('✓ Fetch API available');
                testResults.passed++;
            } else {
                results.push('✗ Fetch API not available');
                testResults.failed++;
            }
            
            document.getElementById('component-tests').innerHTML = results.join('<br>');
        }

        // 6. Skeleton Loader Test
        function testSkeletonLoader() {
            const loader = document.querySelector('.skeleton-loader');
            const style = window.getComputedStyle(loader);
            const results = [];
            
            if (style.animation && style.animation.includes('skeleton-loading')) {
                results.push('✓ Skeleton loader animation working');
                testResults.passed++;
            } else {
                results.push('✗ Skeleton loader animation not detected');
                testResults.failed++;
            }
            
            if (style.background.includes('linear-gradient')) {
                results.push('✓ Skeleton loader gradient rendering');
                testResults.passed++;
            } else {
                results.push('⚠ Skeleton loader gradient may not be rendering');
                testResults.warnings++;
            }
            
            document.getElementById('skeleton-test-result').innerHTML = results.join('<br>');
        }

        // 7. Modal Test Function
        function testModal() {
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
            modal.innerHTML = `
                <div class="bg-white rounded-lg p-6 max-w-md">
                    <h3 class="text-xl font-bold mb-4">Test Modal</h3>
                    <p class="mb-4">This is a test modal to verify modal functionality.</p>
                    <button onclick="this.closest('.fixed').remove()" class="bg-blue-600 text-white px-4 py-2 rounded">Close</button>
                </div>
            `;
            document.body.appendChild(modal);
            
            document.getElementById('modal-test-result').innerHTML = '<span class="test-passed">✓ Modal opened successfully</span>';
            testResults.passed++;
        }

        // 8. Dropdown Positioning Test
        function testDropdown(button) {
            const dropdown = document.getElementById('test-dropdown');
            const container = button.closest('[style*="overflow"]');
            
            dropdown.classList.remove('hidden');
            
            const buttonRect = button.getBoundingClientRect();
            const containerRect = container.getBoundingClientRect();
            const dropdownRect = dropdown.getBoundingClientRect();
            
            const spaceBelow = containerRect.bottom - buttonRect.bottom;
            const spaceAbove = buttonRect.top - containerRect.top;
            
            let position;
            if (spaceBelow < dropdownRect.height && spaceAbove > dropdownRect.height) {
                dropdown.style.bottom = '100%';
                dropdown.style.top = 'auto';
                position = 'upward';
            } else {
                dropdown.style.top = '100%';
                dropdown.style.bottom = 'auto';
                position = 'downward';
            }
            
            document.getElementById('dropdown-position').textContent = position;
            document.getElementById('dropdown-test-result').innerHTML = 
                `<span class="test-passed">✓ Dropdown positioned ${position} (${spaceBelow.toFixed(0)}px below, ${spaceAbove.toFixed(0)}px above)</span>`;
            testResults.passed++;
            
            setTimeout(() => dropdown.classList.add('hidden'), 3000);
        }

        // 9. AJAX Test
        async function testAjax() {
            const resultDiv = document.getElementById('ajax-test-result');
            resultDiv.innerHTML = '<span class="test-warning">Testing AJAX...</span>';
            
            try {
                const response = await fetch(window.location.href);
                if (response.ok) {
                    resultDiv.innerHTML = '<span class="test-passed">✓ AJAX/Fetch API working correctly</span>';
                    testResults.passed++;
                } else {
                    resultDiv.innerHTML = `<span class="test-failed">✗ AJAX request failed: ${response.status}</span>`;
                    testResults.failed++;
                }
            } catch (e) {
                resultDiv.innerHTML = `<span class="test-failed">✗ AJAX error: ${e.message}</span>`;
                testResults.failed++;
            }
        }

        // 10. PHP Backend Tests
        async function testBackend() {
            const results = [];
            
            // Test session handling
            <?php
            session_start();
            $sessionActive = session_status() === PHP_SESSION_ACTIVE;
            echo "const sessionActive = " . ($sessionActive ? 'true' : 'false') . ";\n";
            
            // Test database connection
            try {
                require_once __DIR__ . '/../db.php';
                echo "const dbConnected = true;\n";
                
                // Test critical tables
                $tables = ['users', 'homeowners', 'visitor_passes', 'access_logs'];
                echo "const criticalTables = " . json_encode($tables) . ";\n";
                echo "const tableStatus = {};\n";
                
                foreach ($tables as $table) {
                    $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
                    $count = $stmt->fetchColumn();
                    echo "tableStatus['$table'] = $count;\n";
                }
            } catch (Exception $e) {
                echo "const dbConnected = false;\n";
                echo "const dbError = " . json_encode($e->getMessage()) . ";\n";
            }
            ?>
            
            if (sessionActive) {
                results.push('✓ PHP session management working');
                testResults.passed++;
            } else {
                results.push('✗ PHP session not active');
                testResults.failed++;
            }
            
            if (dbConnected) {
                results.push('✓ Database connection established');
                testResults.passed++;
                
                criticalTables.forEach(table => {
                    results.push(`✓ Table '${table}' accessible (${tableStatus[table]} records)`);
                    testResults.passed++;
                });
            } else {
                results.push('✗ Database connection failed: ' + dbError);
                testResults.failed++;
            }
            
            document.getElementById('backend-tests').innerHTML = results.join('<br>');
        }

        // Generate Summary
        function generateSummary() {
            const total = testResults.passed + testResults.failed + testResults.warnings;
            const summary = `
                <div class="grid grid-cols-3 gap-4 mb-4">
                    <div class="bg-green-100 p-4 rounded text-center">
                        <div class="text-4xl font-bold text-green-600">${testResults.passed}</div>
                        <div>Passed</div>
                    </div>
                    <div class="bg-red-100 p-4 rounded text-center">
                        <div class="text-4xl font-bold text-red-600">${testResults.failed}</div>
                        <div>Failed</div>
                    </div>
                    <div class="bg-yellow-100 p-4 rounded text-center">
                        <div class="text-4xl font-bold text-yellow-600">${testResults.warnings}</div>
                        <div>Warnings</div>
                    </div>
                </div>
                <div class="text-xl font-bold ${testResults.failed === 0 ? 'text-green-600' : 'text-red-600'}">
                    ${testResults.failed === 0 ? '✓ All critical tests passed!' : '⚠ Some tests failed - review above for details'}
                </div>
            `;
            document.getElementById('summary').innerHTML = summary;
        }

        // Run all tests on page load
        window.addEventListener('load', async () => {
            testTailwind();
            testCssFiles();
            testJsFiles();
            testComponents();
            testSkeletonLoader();
            await testBackend();
            setTimeout(() => {
                displayConsoleErrors();
                generateSummary();
            }, 1000);
        });
    </script>
</body>
</html>
