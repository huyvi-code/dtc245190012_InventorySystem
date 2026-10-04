<?php
require_once __DIR__ . '/../config.php';
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Hệ thống Quản lý Kho hàng' ?> | Inventory System DTC245190012</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="bg-light">

<!-- Main Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm">
    <div class="container-fluid px-4">
        <a class="navbar-brand d-flex align-items-center fw-bold" href="/index.php">
            <i class="fa-solid fa-boxes-stacked text-warning me-2 fs-4"></i>
            <span>Kho Hàng <span class="badge bg-primary fs-7">Pro</span></span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarInventory" aria-controls="navbarInventory" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-prev fa-solid fa-bars text-white"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarInventory">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page === 'index.php') ? 'active fw-bold text-white' : '' ?>" href="/index.php">
                        <i class="fa-solid fa-chart-pie me-1"></i> Tổng quan
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page === 'products.php' || $current_page === 'product_add.php' || $current_page === 'product_edit.php') ? 'active fw-bold text-white' : '' ?>" href="/products.php">
                        <i class="fa-solid fa-box-archive me-1"></i> Sản phẩm kho
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page === 'categories.php') ? 'active fw-bold text-white' : '' ?>" href="/categories.php">
                        <i class="fa-solid fa-tags me-1"></i> Danh mục
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page === 'transactions.php') ? 'active fw-bold text-white' : '' ?>" href="/transactions.php">
                        <i class="fa-solid fa-arrow-right-arrow-left me-1"></i> Nhập / Xuất kho
                    </a>
                </li>
            </ul>

            <!-- External Management Links -->
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item me-2">
                    <a class="btn btn-outline-info btn-sm" href="/phpmyadmin/" target="_blank" title="Mở phpMyAdmin Database Manager">
                        <i class="fa-solid fa-database me-1"></i> phpMyAdmin
                    </a>
                </li>
                <li class="nav-item me-2">
                    <a class="btn btn-outline-warning btn-sm" href="/grafana/" target="_blank" title="Mở Grafana Monitoring Dashboard">
                        <i class="fa-solid fa-chart-line me-1"></i> Grafana
                    </a>
                </li>
                <li class="nav-item">
                    <a class="btn btn-outline-success btn-sm" href="/health.php" target="_blank" title="Kiểm tra trạng thái Service">
                        <i class="fa-solid fa-shield-halved me-1"></i> Health
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Page Header & Notification Bar -->
<div class="container-fluid px-4 py-3">
    <?php if ($db_error): ?>
        <div class="alert alert-danger shadow-sm d-flex align-items-center" role="alert">
            <i class="fa-solid fa-triangle-exclamation fs-4 me-3"></i>
            <div>
                <strong>Cảnh báo kết nối:</strong> <?= htmlspecialchars($db_error) ?>. Hãy đảm bảo container MySQL đang chạy và sẵn sàng.
            </div>
        </div>
    <?php endif; ?>

    <?php $flash = get_flash(); if ($flash): ?>
        <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show shadow-sm" role="alert">
            <i class="fa-solid fa-circle-info me-2"></i> <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
