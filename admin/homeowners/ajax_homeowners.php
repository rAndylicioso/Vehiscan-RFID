<?php
require_once __DIR__ . '/../../db.php';

// Retrieve filters (sanitize input)
$name = isset($_GET['name']) ? trim($_GET['name']) : '';
$plate = isset($_GET['plate']) ? trim($_GET['plate']) : '';
$type = isset($_GET['type']) ? trim($_GET['type']) : '';
$color = isset($_GET['color']) ? trim($_GET['color']) : '';
$address = isset($_GET['address']) ? trim($_GET['address']) : '';

$sql = "SELECT * FROM homeowners WHERE account_status = 'approved' AND 1=1";
$params = [];

// Dynamically build the WHERE clause
if ($name !== '') {
    $sql .= " AND name LIKE ?";
    $params[] = "%$name%";
}
if ($plate !== '') {
    $sql .= " AND plate_number LIKE ?";
    $params[] = "%$plate%";
}
if ($type !== '') {
    $sql .= " AND vehicle_type LIKE ?";
    $params[] = "%$type%";
}
if ($color !== '') {
    $sql .= " AND color LIKE ?";
    $params[] = "%$color%";
}
if ($address !== '') {
    $sql .= " AND address LIKE ?";
    $params[] = "%$address%";
}

$sql .= " ORDER BY id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

if (!$rows) {
    echo "<tr><td colspan='9' class='no-data'>No records found</td></tr>";
    exit;
}

foreach ($rows as $row) {
    // Normalize image paths
    $ownerPath = '';
    if (!empty($row['owner_img'])) {
        $ownerPath = $row['owner_img'];
        if (!preg_match('#^uploads/#i', $ownerPath)) {
            $ownerPath = 'uploads/' . ltrim($ownerPath, '/');
        }
    }
    
    $carPath = '';
    if (!empty($row['car_img'])) {
        $carPath = $row['car_img'];
        if (!preg_match('#^uploads/#i', $carPath)) {
            $carPath = 'uploads/' . ltrim($carPath, '/');
        }
    }
    
    echo "<tr>
        <td>{$row['id']}</td>
        <td>" . htmlspecialchars($row['name']) . "</td>
        <td>" . htmlspecialchars($row['contact']) . "</td>
        <td>" . htmlspecialchars($row['address']) . "</td>
        <td>" . htmlspecialchars($row['vehicle_type']) . "</td>
        <td>" . htmlspecialchars($row['color']) . "</td>
        <td>" . htmlspecialchars($row['plate_number']) . "</td>
        <td>";
        if ($ownerPath) {
            echo "<img src='../".htmlspecialchars($ownerPath)."' width='60' onerror=\"this.style.display='none'\">";
        } else {
            echo "—";
        }
    echo "</td><td>";
        if ($carPath) {
            echo "<img src='../".htmlspecialchars($carPath)."' width='60' onerror=\"this.style.display='none'\">";
        } else {
            echo "—";
        }
    echo "</td></tr>";
}
