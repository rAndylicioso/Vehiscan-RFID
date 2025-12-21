<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Functionality Test - VehiScan</title>
    <link rel="stylesheet" href="../assets/css/tailwind.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
    <style>
        .test-section { margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
        .test-pass { background: #d4edda; border-color: #c3e6cb; }
        .test-fail { background: #f8d7da; border-color: #f5c6cb; }
        .test-pending { background: #fff3cd; border-color: #ffeaa7; }
        .log-output { background: #f8f9fa; padding: 10px; border-radius: 4px; font-family: monospace; font-size: 12px; max-height: 200px; overflow-y: auto; }
    </style>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-6xl mx-auto">
        <h1 class="text-3xl font-bold mb-6">🔍 VehiScan Functionality Test Suite</h1>
        
        <!-- Test 1: Chart.js Loading -->
        <div id="test1" class="test-section test-pending">
            <h3 class="text-xl font-bold mb-2">Test 1: Chart.js Library</h3>
            <p class="mb-2">Status: <span id="test1-status">Testing...</span></p>
            <div class="log-output" id="test1-log"></div>
        </div>

        <!-- Test 2: Weekly Stats API -->
        <div id="test2" class="test-section test-pending">
            <h3 class="text-xl font-bold mb-2">Test 2: Weekly Stats API</h3>
            <p class="mb-2">Status: <span id="test2-status">Testing...</span></p>
            <div class="log-output" id="test2-log"></div>
        </div>

        <!-- Test 3: Chart Rendering -->
        <div id="test3" class="test-section test-pending">
            <h3 class="text-xl font-bold mb-2">Test 3: Chart Rendering</h3>
            <p class="mb-2">Status: <span id="test3-status">Testing...</span></p>
            <div style="height: 300px; width: 100%;">
                <canvas id="testChart"></canvas>
            </div>
            <div class="log-output mt-3" id="test3-log"></div>
        </div>

        <!-- Test 4: Database Tables -->
        <div id="test4" class="test-section test-pending">
            <h3 class="text-xl font-bold mb-2">Test 4: Database Tables Check</h3>
            <p class="mb-2">Status: <span id="test4-status">Testing...</span></p>
            <div class="log-output" id="test4-log"></div>
        </div>

        <!-- Test 5: Homeowner Activity API -->
        <div id="test5" class="test-section test-pending">
            <h3 class="text-xl font-bold mb-2">Test 5: Homeowner Activity API</h3>
            <p class="mb-2">Status: <span id="test5-status">Testing...</span></p>
            <div class="log-output" id="test5-log"></div>
        </div>

        <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded">
            <h3 class="font-bold text-blue-900 mb-2">Testing Instructions:</h3>
            <ol class="list-decimal list-inside text-sm text-blue-800">
                <li>This page runs automatic tests on page load</li>
                <li>Green = Pass, Red = Fail, Yellow = Pending</li>
                <li>Check console (F12) for detailed debug logs</li>
                <li>If tests fail, check the error messages for fixes needed</li>
            </ol>
        </div>
    </div>

    <script>
        const log = (testId, message, isError = false) => {
            const logEl = document.getElementById(`${testId}-log`);
            const timestamp = new Date().toLocaleTimeString();
            const color = isError ? 'red' : 'green';
            logEl.innerHTML += `<div style="color: ${color}">[${timestamp}] ${message}</div>`;
            console.log(`[${testId}]`, message);
        };

        const setStatus = (testId, status, message) => {
            const testEl = document.getElementById(testId);
            const statusEl = document.getElementById(`${testId}-status`);
            
            testEl.classList.remove('test-pass', 'test-fail', 'test-pending');
            if (status === 'pass') {
                testEl.classList.add('test-pass');
                statusEl.textContent = '✅ PASS';
            } else if (status === 'fail') {
                testEl.classList.add('test-fail');
                statusEl.textContent = '❌ FAIL';
            } else {
                statusEl.textContent = message || 'Testing...';
            }
        };

        // Test 1: Chart.js Loading
        function test1_ChartJS() {
            log('test1', 'Checking if Chart.js is loaded...');
            
            if (typeof Chart !== 'undefined') {
                log('test1', `✅ Chart.js version ${Chart.version} loaded successfully`);
                setStatus('test1', 'pass', '✅ PASS');
                return true;
            } else {
                log('test1', '❌ Chart.js not loaded! Check CDN link.', true);
                setStatus('test1', 'fail', '❌ FAIL');
                return false;
            }
        }

        // Test 2: Weekly Stats API
        async function test2_WeeklyStats() {
            log('test2', 'Fetching weekly stats from API...');
            
            try {
                const response = await fetch('../admin/api/get_weekly_stats.php');
                log('test2', `Response status: ${response.status}`);
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const data = await response.json();
                log('test2', `Response: ${JSON.stringify(data)}`);
                
                if (data.success) {
                    log('test2', `✅ Got ${data.labels.length} days of data`);
                    log('test2', `Labels: ${data.labels.join(', ')}`);
                    log('test2', `Values: ${data.values.join(', ')}`);
                    setStatus('test2', 'pass', '✅ PASS');
                    return data;
                } else {
                    throw new Error(data.error || 'API returned success: false');
                }
            } catch (error) {
                log('test2', `❌ Error: ${error.message}`, true);
                setStatus('test2', 'fail', '❌ FAIL');
                return null;
            }
        }

        // Test 3: Chart Rendering
        async function test3_ChartRender(data) {
            if (!data) {
                log('test3', '⚠️ Skipped - no data from Test 2', true);
                setStatus('test3', 'fail', '❌ SKIP');
                return;
            }
            
            log('test3', 'Attempting to render test chart...');
            
            try {
                const ctx = document.getElementById('testChart');
                
                if (!ctx) {
                    throw new Error('Canvas element not found');
                }
                
                const chart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: 'Test Data',
                            data: data.values,
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });
                
                log('test3', '✅ Chart rendered successfully');
                setStatus('test3', 'pass', '✅ PASS');
            } catch (error) {
                log('test3', `❌ Error: ${error.message}`, true);
                setStatus('test3', 'fail', '❌ FAIL');
            }
        }

        // Test 4: Database Tables
        async function test4_DatabaseTables() {
            log('test4', 'Checking database table structure...');
            
            try {
                const response = await fetch('test_db_structure.php');
                const data = await response.json();
                
                if (data.success) {
                    log('test4', `✅ Database connection OK`);
                    log('test4', `Tables found: ${data.tables.join(', ')}`);
                    
                    // Check critical tables
                    const critical = ['recent_logs', 'homeowners', 'admins'];
                    const missing = critical.filter(t => !data.tables.includes(t));
                    
                    if (missing.length > 0) {
                        log('test4', `⚠️ Missing tables: ${missing.join(', ')}`, true);
                        setStatus('test4', 'fail', '❌ FAIL');
                    } else {
                        log('test4', '✅ All critical tables exist');
                        
                        if (data.columns.recent_logs) {
                            log('test4', `recent_logs columns: ${data.columns.recent_logs.join(', ')}`);
                        }
                        
                        setStatus('test4', 'pass', '✅ PASS');
                    }
                } else {
                    throw new Error(data.error);
                }
            } catch (error) {
                log('test4', `❌ Error: ${error.message}`, true);
                setStatus('test4', 'fail', '❌ FAIL');
            }
        }

        // Test 5: Homeowner Activity (requires authentication - will fail if not logged in)
        async function test5_HomeownerActivity() {
            log('test5', 'Testing homeowner activity API...');
            
            try {
                const response = await fetch('../homeowners/api/get_my_activity.php?days=7');
                
                if (response.status === 403) {
                    log('test5', '⚠️ Not logged in as homeowner (expected if testing from admin)');
                    setStatus('test5', 'pass', '✅ SKIP (Auth required)');
                    return;
                }
                
                const data = await response.json();
                
                if (data.success) {
                    log('test5', `✅ Got ${data.logs.length} activity logs`);
                    log('test5', `Stats: ${JSON.stringify(data.stats)}`);
                    setStatus('test5', 'pass', '✅ PASS');
                } else {
                    throw new Error(data.error);
                }
            } catch (error) {
                log('test5', `❌ Error: ${error.message}`, true);
                setStatus('test5', 'fail', '❌ FAIL');
            }
        }

        // Run all tests
        async function runAllTests() {
            console.log('=== VehiScan Functionality Test Suite Started ===');
            
            // Test 1: Chart.js
            const chartJSLoaded = test1_ChartJS();
            
            // Test 2: Weekly Stats API
            const weeklyData = await test2_WeeklyStats();
            
            // Test 3: Chart Rendering
            if (chartJSLoaded) {
                await test3_ChartRender(weeklyData);
            }
            
            // Test 4: Database
            await test4_DatabaseTables();
            
            // Test 5: Homeowner Activity
            await test5_HomeownerActivity();
            
            console.log('=== All Tests Complete ===');
        }

        // Run tests when page loads
        window.addEventListener('load', runAllTests);
    </script>
</body>
</html>
