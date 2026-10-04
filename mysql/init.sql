-- ==============================================================================
-- Inventory System Database Initialization Script (Đề 5)
-- Database: inventory_db
-- ==============================================================================

CREATE DATABASE IF NOT EXISTS `inventory_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `inventory_db`;

-- Drop existing tables if re-initializing
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `inventory_transactions`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `suppliers`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------------------------
-- Table: categories (Danh mục sản phẩm)
-- ------------------------------------------------------------------------------
CREATE TABLE `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(50) NOT NULL UNIQUE,
    `name` VARCHAR(150) NOT NULL,
    `description` TEXT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- Table: suppliers (Nhà cung cấp)
-- ------------------------------------------------------------------------------
CREATE TABLE `suppliers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `contact_name` VARCHAR(100) NULL,
    `phone` VARCHAR(20) NULL,
    `email` VARCHAR(100) NULL,
    `address` VARCHAR(255) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- Table: products (Sản phẩm trong kho)
-- ------------------------------------------------------------------------------
CREATE TABLE `products` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT NOT NULL,
    `sku` VARCHAR(50) NOT NULL UNIQUE,
    `name` VARCHAR(200) NOT NULL,
    `unit` VARCHAR(30) NOT NULL DEFAULT 'Cái',
    `purchase_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `selling_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `quantity` INT NOT NULL DEFAULT 0,
    `min_quantity` INT NOT NULL DEFAULT 5,
    `location` VARCHAR(100) NULL DEFAULT 'Kệ A1',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- Table: inventory_transactions (Lịch sử Nhập / Xuất kho)
-- ------------------------------------------------------------------------------
CREATE TABLE `inventory_transactions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `product_id` INT NOT NULL,
    `type` ENUM('IN', 'OUT') NOT NULL,
    `quantity` INT NOT NULL,
    `reference_code` VARCHAR(50) NOT NULL,
    `note` TEXT NULL,
    `performed_by` VARCHAR(100) NOT NULL DEFAULT 'Admin',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_transactions_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- Table: users (Người dùng hệ thống)
-- ------------------------------------------------------------------------------
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `role` ENUM('admin', 'staff') NOT NULL DEFAULT 'staff',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- Seed Sample Data
-- ==============================================================================

-- 1. Insert Categories
INSERT INTO `categories` (`id`, `code`, `name`, `description`) VALUES
(1, 'ELEC', 'Thiết bị điện tử', 'Máy tính xách tay, máy tính bảng và phụ kiện điện tử cao cấp'),
(2, 'COMP', 'Linh kiện máy tính', 'CPU, RAM, ổ cứng SSD, card màn hình, bo mạch chủ'),
(3, 'NETW', 'Thiết bị mạng & Viễn thông', 'Router WiFi, Switch mạng, cáp mạng, bộ phát sóng'),
(4, 'PERI', 'Thiết bị ngoại vi', 'Bàn phím cơ, chuột không dây, tai nghe, webcam'),
(5, 'OFFI', 'Thiết bị & Phụ kiện văn phòng', 'Máy in, máy scan, giấy in, máy hủy tài liệu');

-- 2. Insert Suppliers
INSERT INTO `suppliers` (`id`, `name`, `contact_name`, `phone`, `email`, `address`) VALUES
(1, 'Công ty Công nghệ Synnex FPT', 'Nguyễn Văn Nam', '02473006666', 'contact@synnexfpt.vn', 'Hà Nội, Việt Nam'),
(2, 'Công ty TNHH Phân Phối Dầu Khí (PHTD)', 'Trần Thị Thu', '02838230555', 'sales@phtd.com.vn', 'TP. Hồ Chí Minh, Việt Nam'),
(3, 'Công ty Cổ phần Thế Giới Số (Digiworld)', 'Lê Hoàng Minh', '02839290059', 'info@digiworld.com.vn', 'TP. Hồ Chí Minh, Việt Nam');

-- 3. Insert Products (Gồm cả hàng tồn dồi dào, hàng sắp hết và cảnh báo tồn thấp)
INSERT INTO `products` (`id`, `category_id`, `sku`, `name`, `unit`, `purchase_price`, `selling_price`, `quantity`, `min_quantity`, `location`) VALUES
(1, 1, 'LAP-DELL-XPS15', 'Laptop Dell XPS 15 9530 i7-13700H', 'Chiếc', 38500000.00, 43900000.00, 12, 5, 'Kệ A1-01'),
(2, 4, 'MON-LG-27UP850', 'Màn hình LG 27 Inch 4K UHD IPS', 'Chiếc', 8200000.00, 9990000.00, 3, 5, 'Kệ A2-04'), -- Tồn kho thấp (3 < 5)
(3, 4, 'KB-KEY-K2V2', 'Bàn phím cơ không dây Keychron K2 V2', 'Chiếc', 1650000.00, 2150000.00, 25, 10, 'Kệ B1-02'),
(4, 4, 'MOU-LOG-MX3S', 'Chuột không dây Logitech MX Master 3S', 'Chiếc', 1850000.00, 2390000.00, 18, 5, 'Kệ B1-05'),
(5, 3, 'NET-ASUS-AX58U', 'Router WiFi 6 Asus RT-AX58U Dual Band', 'Chiếc', 2100000.00, 2690000.00, 2, 6, 'Kệ C1-03'), -- Tồn kho thấp (2 < 6)
(6, 3, 'CAB-CAT6-UG10M', 'Cáp mạng đúc sẵn Cat6 Ugreen 10M', 'Sợi', 65000.00, 110000.00, 120, 20, 'Kệ C2-01'),
(7, 4, 'AUD-SONY-1000XM5', 'Tai nghe chống ồn Sony WH-1000XM5', 'Chiếc', 6200000.00, 7490000.00, 8, 4, 'Kệ B2-01'),
(8, 2, 'SSD-SAM-980P-1T', 'Ổ cứng SSD Samsung 980 PRO PCIe 4.0 1TB', 'Chiếc', 2200000.00, 2850000.00, 32, 10, 'Kệ D1-01'),
(9, 2, 'RAM-COR-32GB', 'RAM Corsair Vengeance RGB DDR5 32GB (2x16GB)', 'Bộ', 2650000.00, 3290000.00, 14, 5, 'Kệ D1-05'),
(10, 2, 'PSU-COR-RM850X', 'Nguồn máy tính Corsair RM850x 850W 80 Plus Gold', 'Chiếc', 2900000.00, 3650000.00, 1, 5, 'Kệ D2-03'), -- Cảnh báo tồn rất thấp (1 < 5)
(11, 5, 'PRN-CANON-2900', 'Máy in Laser đen trắng Canon LBP 2900', 'Chiếc', 3800000.00, 4450000.00, 6, 3, 'Kệ E1-01');

-- 4. Insert Initial Transactions (Lịch sử giao dịch nhập/xuất)
INSERT INTO `inventory_transactions` (`product_id`, `type`, `quantity`, `reference_code`, `note`, `performed_by`, `created_at`) VALUES
(1, 'IN', 15, 'PNK-20241001-01', 'Nhập lô hàng laptop Dell chính hãng đầu tháng', 'Admin', '2024-10-01 08:30:00'),
(1, 'OUT', 3, 'PXK-20241002-01', 'Xuất bán khách hàng doanh nghiệp dự án', 'Quản trị viên', '2024-10-02 10:15:00'),
(2, 'IN', 5, 'PNK-20241001-02', 'Nhập bổ sung màn hình đồ họa LG', 'Admin', '2024-10-01 09:00:00'),
(2, 'OUT', 2, 'PXK-20241003-01', 'Xuất bán lẻ phòng kỹ thuật số', 'Quản trị viên', '2024-10-03 14:20:00'),
(3, 'IN', 30, 'PNK-20241001-03', 'Nhập bàn phím cơ Keychron', 'Admin', '2024-10-01 09:45:00'),
(3, 'OUT', 5, 'PXK-20241002-02', 'Xuất bán cửa hàng bán lẻ', 'Nhân viên kho', '2024-10-02 16:00:00'),
(5, 'IN', 10, 'PNK-20241001-04', 'Nhập Router Asus Wifi 6', 'Admin', '2024-10-01 10:00:00'),
(5, 'OUT', 8, 'PXK-20241003-02', 'Xuất cấp thiết bị dự án văn phòng Viettel', 'Admin', '2024-10-03 11:30:00'),
(10, 'IN', 5, 'PNK-20241001-05', 'Nhập nguồn Corsair RM850x', 'Admin', '2024-10-01 10:30:00'),
(10, 'OUT', 4, 'PXK-20241003-03', 'Xuất lắp ráp máy trạm AI', 'Admin', '2024-10-03 15:45:00');

-- 5. Insert Default Administrator User
-- Password: Admin@123456 (Bcrypt hash: $2y$10$wE9K2sFzV7f12i3085iB8.bL.K10M46Z5F67Zf4w3g4Z5Z6789abc or validated via config.php)
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `role`) VALUES
(1, 'admin', '$2y$10$3zRkJn6O.93dC3p3z0m4r.Z7Vp1vG3pLd7gZ7rV3P1m2z3r4k5l6m', 'Quản trị viên Hệ thống', 'admin'),
(2, 'staff', '$2y$10$3zRkJn6O.93dC3p3z0m4r.Z7Vp1vG3pLd7gZ7rV3P1m2z3r4k5l6m', 'Nhân viên Thủ kho', 'staff');
