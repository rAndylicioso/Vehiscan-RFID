<?php
session_start();
$_SESSION['role'] = 'guard';
$_SESSION['username'] = 'test';
$_GET['page'] = 1;

ob_start();
include __DIR__ . '/fetch/fetch_logs.php';
$content = ob_get_clean();

echo "Content length: " . strlen($content) . PHP_EOL;
echo "First 1000 chars:" . PHP_EOL;
echo substr($content, 0, 1000) . PHP_EOL;
echo "---" . PHP_EOL;
echo "Contains 'wadasdaw': " . (strpos($content, 'wadasdaw') !== false ? 'YES' : 'NO') . PHP_EOL;
echo "Contains 'grid': " . (strpos($content, 'grid') !== false ? 'YES' : 'NO') . PHP_EOL;
echo "Contains 'No access logs': " . (strpos($content, 'No access logs') !== false ? 'YES' : 'NO') . PHP_EOL;
