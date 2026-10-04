<?php
require_once __DIR__ . '/config.php';

$id = (int)($_GET['id'] ?? 0);

if ($id && $pdo) {
    try {
        $stmt = $pdo->prepare("SELECT name FROM products WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $name = $stmt->fetchColumn();

        if ($name) {
            $del = $pdo->prepare("DELETE FROM products WHERE id = :id");
            $del->execute([':id' => $id]);
            set_flash('success', "Đã xóa sản phẩm [{$name}] thành công!");
        } else {
            set_flash('warning', "Sản phẩm không tồn tại.");
        }
    } catch (PDOException $e) {
        set_flash('danger', "Không thể xóa sản phẩm do có lịch sử giao dịch liên quan.");
    }
}

header("Location: /products.php");
exit;
