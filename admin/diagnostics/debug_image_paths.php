<?php
require_once __DIR__ . '/../../includes/session_admin_unified.php';
require_once __DIR__ . '/../../db.php';

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Debug Image Paths</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1e1e1e; color: #d4d4d4; }
        h1 { color: #4ec9b0; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #444; padding: 8px; text-align: left; }
        th { background: #2d2d2d; color: #4fc1ff; }
        .missing { background: #3a1a1a; color: #f48771; }
        .exists { background: #1a3a1a; color: #73c991; }
        .raw { color: #ce9178; }
        .norm { color: #9cdcfe; }
        .path { color: #608b4e; font-size: 0.9em; }
    </style>
</head>
<body>
<h1>🔍 Image Path Debug Report</h1>

<?php
try {
    $uploadsDir = realpath(__DIR__ . '/../uploads');
    echo "<p><strong>Uploads directory:</strong> <span class='path'>$uploadsDir</span></p>";
    
    // Fetch all homeowners with raw image fields
    $stmt = $pdo->query("SELECT id, name, plate_number, owner_img, car_img FROM homeowners WHERE name IS NOT NULL ORDER BY id ASC LIMIT 20");
    $homeowners = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>📊 First 20 Homeowners</h2>";
    echo "<table>";
    echo "<tr><th>ID</th><th>Name</th><th>Plate</th><th>Owner Image (raw DB)</th><th>Normalized</th><th>File Exists?</th><th>Car Image (raw DB)</th><th>Normalized</th><th>File Exists?</th></tr>";
    
    foreach ($homeowners as $h) {
        echo "<tr>";
        echo "<td>{$h['id']}</td>";
        echo "<td>" . htmlspecialchars($h['name']) . "</td>";
        echo "<td>" . htmlspecialchars($h['plate_number']) . "</td>";
        
        // Owner image
        $ownerRaw = $h['owner_img'];
        echo "<td class='raw'>" . ($ownerRaw ? htmlspecialchars($ownerRaw) : '<em>NULL</em>') . "</td>";
        
        $normOwner = null;
        if (!empty($ownerRaw)) {
            $p = ltrim($ownerRaw, '/');
            if (preg_match('#^uploads/#i', $p)) {
                $normOwner = $p;
            } elseif (preg_match('#^homeowners/#i', $p)) {
                $normOwner = 'uploads/' . preg_replace('#^homeowners/#i', 'homeowners/', $p);
            } elseif (preg_match('#^vehicles/#i', $p)) {
                $normOwner = 'uploads/' . preg_replace('#^vehicles/#i', 'vehicles/', $p);
            } else {
                $normOwner = 'uploads/homeowners/' . basename($p);
            }
        }
        
        echo "<td class='norm'>" . ($normOwner ? htmlspecialchars($normOwner) : '<em>-</em>') . "</td>";
        
        $ownerExists = false;
        if ($normOwner) {
            $rel = preg_replace('#^uploads/#i', '', ltrim($normOwner, '/'));
            $serverPath = $uploadsDir . '/' . $rel;
            $ownerExists = is_readable($serverPath) && is_file($serverPath);
            $statusClass = $ownerExists ? 'exists' : 'missing';
            echo "<td class='$statusClass'>" . ($ownerExists ? '✓ YES' : '✗ NO') . "<br><span class='path'>$serverPath</span></td>";
        } else {
            echo "<td>-</td>";
        }
        
        // Car image
        $carRaw = $h['car_img'];
        echo "<td class='raw'>" . ($carRaw ? htmlspecialchars($carRaw) : '<em>NULL</em>') . "</td>";
        
        $normCar = null;
        if (!empty($carRaw)) {
            $p2 = ltrim($carRaw, '/');
            if (preg_match('#^uploads/#i', $p2)) {
                $normCar = $p2;
            } elseif (preg_match('#^vehicles/#i', $p2)) {
                $normCar = 'uploads/' . preg_replace('#^vehicles/#i', 'vehicles/', $p2);
            } elseif (preg_match('#^homeowners/#i', $p2)) {
                $normCar = 'uploads/' . preg_replace('#^homeowners/#i', 'homeowners/', $p2);
            } else {
                $normCar = 'uploads/vehicles/' . basename($p2);
            }
        }
        
        echo "<td class='norm'>" . ($normCar ? htmlspecialchars($normCar) : '<em>-</em>') . "</td>";
        
        $carExists = false;
        if ($normCar) {
            $rel2 = preg_replace('#^uploads/#i', '', ltrim($normCar, '/'));
            $serverPath2 = $uploadsDir . '/' . $rel2;
            $carExists = is_readable($serverPath2) && is_file($serverPath2);
            $statusClass = $carExists ? 'exists' : 'missing';
            echo "<td class='$statusClass'>" . ($carExists ? '✓ YES' : '✗ NO') . "<br><span class='path'>$serverPath2</span></td>";
        } else {
            echo "<td>-</td>";
        }
        
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Show actual files in uploads/homeowners
    echo "<h2>📁 Actual Files in uploads/homeowners/</h2>";
    $homeownerFiles = glob($uploadsDir . '/homeowners/*');
    if ($homeownerFiles) {
        echo "<ul>";
        foreach (array_slice($homeownerFiles, 0, 20) as $file) {
            $basename = basename($file);
            echo "<li class='path'>$basename</li>";
        }
        echo "</ul>";
        echo "<p><em>Showing first 20 of " . count($homeownerFiles) . " total files</em></p>";
    } else {
        echo "<p class='missing'>No files found!</p>";
    }
    
    // Show actual files in uploads/vehicles
    echo "<h2>🚗 Actual Files in uploads/vehicles/</h2>";
    $vehicleFiles = glob($uploadsDir . '/vehicles/*');
    if ($vehicleFiles) {
        echo "<ul>";
        foreach (array_slice($vehicleFiles, 0, 20) as $file) {
            $basename = basename($file);
            echo "<li class='path'>$basename</li>";
        }
        echo "</ul>";
        echo "<p><em>Showing first 20 of " . count($vehicleFiles) . " total files</em></p>";
    } else {
        echo "<p class='missing'>No files found!</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='missing'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<p><a href="admin_panel.php" style="color: #4fc1ff;">← Back to Admin Panel</a></p>
</body>
</html>
