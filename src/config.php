<?php
/**
 * Inventory Management System - Database Configuration & Global Helpers
 * DTC245190012 - Đề 5: Hệ thống Quản lý Kho hàng
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Credentials from Environment variables (with production defaults)
$db_host = getenv('DB_HOST') ?: 'db';
$db_port = getenv('DB_PORT') ?: '3306';
$db_name = getenv('DB_NAME') ?: 'inventory_db';
$db_user = getenv('DB_USER') ?: 'inventory_user';
$db_pass = getenv('DB_PASS') ?: 'InventorySecurePass2024!';

$pdo = null;
$db_error = null;

try {
    $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ];
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $e) {
    $db_error = "Lỗi kết nối cơ sở dữ liệu: " . $e->getMessage();
}

/**
 * Format currency to Vietnamese Dong (VNĐ)
 */
function format_currency($amount) {
    return number_format((float)$amount, 0, ',', '.') . ' ₫';
}

/**
 * Format datetime string
 */
function format_datetime($dt) {
    if (!$dt) return '-';
    $time = strtotime($dt);
    return date('d/m/Y H:i', $time);
}

/**
 * Sanitize user input
 */
function clean_input($data) {
    return htmlspecialchars(trim($data ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Set flash alert message
 */
function set_flash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type, // success, danger, warning, info
        'message' => $message
    ];
}

/**
 * Display and clear flash message
 */
function get_flash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}
