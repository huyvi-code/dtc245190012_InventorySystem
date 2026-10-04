<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config.php';

$status = [
    'service' => 'Inventory System (DTC245190012)',
    'status' => 'UP',
    'timestamp' => date('c'),
    'php_version' => PHP_VERSION,
    'memory_usage' => memory_get_usage(true),
    'database' => [
        'status' => 'DOWN',
        'latency_ms' => null,
        'error' => null
    ]
];

if ($pdo) {
    try {
        $start = microtime(true);
        $pdo->query("SELECT 1");
        $duration = round((microtime(true) - $start) * 1000, 2);
        
        $status['database']['status'] = 'CONNECTED';
        $status['database']['latency_ms'] = $duration;
        http_response_code(200);
    } catch (Exception $e) {
        $status['status'] = 'DEGRADED';
        $status['database']['error'] = $e->getMessage();
        http_response_code(503);
    }
} else {
    $status['status'] = 'DOWN';
    $status['database']['error'] = $db_error ?? 'Chưa khởi tạo kết nối CSDL';
    http_response_code(503);
}

echo json_encode($status, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
exit;
