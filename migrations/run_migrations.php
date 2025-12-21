<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Migrations - VehiScan</title>
    <link rel="stylesheet" href="../assets/css/tailwind.css">
    <style>
        .migration-status { padding: 10px; margin: 5px 0; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .pending { background: #fff3cd; color: #856404; }
    </style>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto bg-white rounded-lg shadow-lg p-8">
        <h1 class="text-3xl font-bold mb-6">Database Migrations</h1>
        
        <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded">
            <p class="text-yellow-800 font-semibold">⚠️ WARNING: This will modify your database structure!</p>
            <p class="text-sm text-yellow-700 mt-2">Backup your database before proceeding.</p>
        </div>

        <div id="migration-status"></div>

        <button id="runMigrations" class="mt-6 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold">
            Run All Migrations
        </button>
    </div>

    <script>
        document.getElementById('runMigrations').addEventListener('click', async () => {
            const statusDiv = document.getElementById('migration-status');
            const btn = document.getElementById('runMigrations');
            
            btn.disabled = true;
            btn.textContent = 'Running migrations...';
            statusDiv.innerHTML = '';

            try {
                const response = await fetch('run_migrations_process.php', {
                    method: 'POST'
                });
                
                const result = await response.json();
                
                result.migrations.forEach(migration => {
                    const div = document.createElement('div');
                    div.className = `migration-status ${migration.status}`;
                    div.innerHTML = `
                        <strong>${migration.file}</strong><br>
                        ${migration.message}
                    `;
                    statusDiv.appendChild(div);
                });

                btn.textContent = 'Migrations Complete!';
                btn.className = 'mt-6 px-6 py-3 bg-green-600 text-white rounded-lg font-semibold';
                
            } catch (error) {
                statusDiv.innerHTML = `<div class="migration-status error">
                    <strong>Error:</strong> ${error.message}
                </div>`;
                btn.disabled = false;
                btn.textContent = 'Run All Migrations';
            }
        });
    </script>
</body>
</html>
