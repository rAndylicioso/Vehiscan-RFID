<?php
session_start();
$_SESSION['role'] = 'guard';
$_SESSION['username'] = 'test';
$_GET['page'] = 2;

ob_start();
include __DIR__ . '/fetch/fetch_logs.php';
$content = ob_get_clean();

echo "Content length: " . strlen($content) . PHP_EOL;
echo "Total pages check:" . PHP_EOL;
echo "Contains 'pagination-btn': " . (strpos($content, 'pagination-btn') !== false ? 'YES' : 'NO') . PHP_EOL;
echo "Contains 'data-page': " . (strpos($content, 'data-page') !== false ? 'YES' : 'NO') . PHP_EOL;
echo "Number of pagination-btn occurrences: " . substr_count($content, 'pagination-btn') . PHP_EOL;
echo PHP_EOL;
echo "Last 1000 chars:" . PHP_EOL;
echo substr($content, -1000) . PHP_EOL;
