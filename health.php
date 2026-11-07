<?php
header('Content-Type: text/plain; charset=utf-8');
echo "PHP " . PHP_VERSION . "\n";
echo "SAPI " . PHP_SAPI . "\n";
echo "PDO drivers: " . (class_exists('PDO') ? implode(', ', PDO::getAvailableDrivers()) : 'нет PDO') . "\n";

define('ROOT_PATH', __DIR__);
require ROOT_PATH . '/config.php';

echo "DB_HOST " . DB_HOST . "\n";
try {
    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5,
    ));
    echo "MySQL: OK\n";
} catch (Exception $e) {
    echo "MySQL: FAIL " . $e->getMessage() . "\n";
}

$writable = is_writable(__DIR__) ? 'yes' : 'no';
echo "docs writable: " . $writable . "\n";
echo "storage: " . (is_dir(__DIR__ . '/storage') ? 'exists' : 'missing') . "\n";
echo "OK\n";
