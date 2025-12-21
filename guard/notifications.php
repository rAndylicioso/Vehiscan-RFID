<?php
require_once __DIR__ . '/../includes/session_guard.php';
require_once __DIR__ . '/../db.php';

// OPTIONAL: Restrict access (e.g., only guards or admins)
if (!isset($_SESSION['role'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Handle mark-all-as-read
if (isset($_POST['mark_all_read'])) {
    $pdo->query("UPDATE notifications SET is_read = 1");
    header("Location: notifications.php");
    exit();
}

// Handle mark single notification as read
if (isset($_GET['mark_read'])) {
    $id = intval($_GET['mark_read']);
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: notifications.php");
    exit();
}

// Fetch all notifications
$stmt = $pdo->query("SELECT * FROM notifications ORDER BY created_at DESC");
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Notifications</title>
<style>
    body {
        font-family: "Segoe UI", sans-serif;
        background-color: #f4f6f8;
        margin: 20px;
    }
    h2 {
        margin-bottom: 15px;
    }
    .top-bar {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    th, td {
        padding: 10px;
        border-bottom: 1px solid #ddd;
        font-size: 14px;
        text-align: left;
    }
    th {
        background-color: #f5f5f5;
        font-weight: 600;
    }
    .status-unread {
        color: #e74c3c;
        font-weight: bold;
    }
    .status-read {
        color: #7f8c8d;
    }
    .btn {
        padding: 6px 12px;
        font-size: 13px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
    }
    .btn-read {
        background-color: #3498db;
        color: white;
    }
    .btn-all-read {
        background-color: #2ecc71;
        color: white;
    }
    .btn-back {
        background-color: #7f8c8d;
        color: white;
    }
</style>
</head>
<body>

<h2>All Notifications</h2>

<div class="top-bar">
    <form method="POST" style="margin:0;">
        <button type="submit" name="mark_all_read" class="btn btn-all-read">
            Mark All as Read
        </button>
    </form>
    <a href="user_side.php" class="btn btn-back">← Back</a>
</div>

<table>
    <thead>
        <tr>
            <th>Type</th>
            <th>Message</th>
            <th>Date</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($notifications)): ?>
            <tr>
                <td colspan="5" style="text-align:center;">No notifications found.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($notifications as $n): ?>
                <tr>
                    <td><?php echo htmlspecialchars($n['type']); ?></td>
                    <td><?php echo htmlspecialchars($n['message']); ?></td>
                    <td><?php echo htmlspecialchars($n['created_at']); ?></td>
                    <td class="<?php echo $n['is_read'] ? 'status-read' : 'status-unread'; ?>">
                        <?php echo $n['is_read'] ? 'Read' : 'Unread'; ?>
                    </td>
                    <td>
                        <?php if (!$n['is_read']): ?>
                            <a href="notifications.php?mark_read=<?php echo $n['id']; ?>" class="btn btn-read">
                                Mark as Read
                            </a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

</body>
</html>
