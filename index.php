<?php
require_once __DIR__ . '/includes/session_config.php';
if (!isset($_SESSION['username'])) {
    header("Location: auth/login.php");
    exit;
}
?>
