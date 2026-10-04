<?php
$page_title = "Quản lý Danh mục Hàng";
require_once __DIR__ . '/includes/header.php';

$errors = [];

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $code = strtoupper(clean_input($_POST['code'] ?? ''));
    $name = clean_input($_POST['name'] ?? '');
    $description = clean_input($_POST['description'] ?? '');

    if (empty($code) || empty($name)) {
        $errors[] = "Vui lòng nhập đầy đủ Mã danh mục và Tên danh mục.";
    }

    if (empty($errors) && $pdo) {
        if ($action === 'create') {
            $chk = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE code = :code");
            $chk->execute([':code' => $code]);
            if ($chk->fetchColumn() > 0) {
                $errors[] = "Mã danh mục '$code' đã tồn tại.";
            } else {
                $ins = $pdo->prepare("INSERT INTO categories (code, name, description) VALUES (:code, :name, :description)");
                $ins->execute([':code' => $code, ':name' => $name, ':description' => $description]);
                set_flash('success', "Thêm danh mục '$name' thành công!");
                header("Location: /categories.php");
                exit;
            }
        } elseif ($action === 'update') {
            $cat_id = (int)$_POST['category_id'];
            $upd = $pdo->prepare("UPDATE categories SET code = :code, name = :name, description = :description WHERE id = :id");
            $upd->execute([':code' => $code, ':name' => $name, ':description' => $description, ':id' => $cat_id]);
            set_flash('success', "Cập nhật danh mục '$name' thành công!");
            header("Location: /categories.php");
            exit;
        }
    }
}

// Handle Delete
if (isset($_GET['delete']) && $pdo) {
    $del_id = (int)$_GET['delete'];
    // Check if category has products
    $check_p = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = :id");
    $check_p->execute([':id' => $del_id]);
    if ($check_p->fetchColumn() > 0) {
        set_flash('danger', "Không thể xóa danh mục này vì đang có sản phẩm thuộc danh mục!");
    } else {
        $del = $pdo->prepare("DELETE FROM categories WHERE id = :id");
        $del->execute([':id' => $del_id]);
        set_flash('success', "Đã xóa danh mục thành công!");
    }
    header("Location: /categories.php");
    exit;
}

// Fetch categories with product counts
$categories = [];
if ($pdo) {
    $stmt = $pdo->query("SELECT c.*, COUNT(p.id) as product_count, COALESCE(SUM(p.quantity), 0) as total_stock 
        FROM categories c 
        LEFT JOIN products p ON c.id = p.category_id 
        GROUP BY c.id ORDER BY c.name ASC");
    $categories = $stmt->fetchAll();
}
?>

<div class="row g-4">
    <!-- Add Category Form -->
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 sticky-top" style="top: 80px;">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="fw-bold mb-0 text-primary"><i class="fa-solid fa-folder-plus me-2"></i>Thêm Danh mục Mới</h6>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger py-2" role="alert">
                        <ul class="mb-0 ps-3 small">
                            <?php foreach ($errors as $e): ?>
                                <li><?= htmlspecialchars($e) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="/categories.php">
                    <input type="hidden" name="action" value="create">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Mã danh mục <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control" placeholder="VD: ELEC, COMP, NETW" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Tên danh mục <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="VD: Thiết bị điện tử" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Mô tả chi tiết</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Ghi chú về nhóm mặt hàng..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fa-solid fa-plus me-1"></i> Lưu Danh mục
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Category List Table -->
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-tags text-primary me-2"></i>Danh sách Danh mục Hàng tồn kho</h6>
                <span class="badge bg-secondary"><?= count($categories) ?> danh mục</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 70px;">Mã</th>
                            <th>Tên danh mục</th>
                            <th>Mô tả</th>
                            <th class="text-center">Số mã hàng</th>
                            <th class="text-center">Tổng tồn</th>
                            <th class="text-center" style="width: 90px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categories)): ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">Chưa có danh mục nào.</td></tr>
                        <?php else: ?>
                            <?php foreach ($categories as $c): ?>
                                <tr>
                                    <td><span class="badge bg-dark-subtle text-dark border"><?= htmlspecialchars($c['code']) ?></span></td>
                                    <td class="fw-bold text-primary">
                                        <a href="/products.php?category_id=<?= $c['id'] ?>" class="text-decoration-none">
                                            <?= htmlspecialchars($c['name']) ?>
                                        </a>
                                    </td>
                                    <td class="small text-muted"><?= htmlspecialchars($c['description'] ?: '-') ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-primary-subtle text-primary border"><?= $c['product_count'] ?> mã</span>
                                    </td>
                                    <td class="text-center fw-semibold text-secondary">
                                        <?= number_format($c['total_stock']) ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($c['product_count'] == 0): ?>
                                            <a href="/categories.php?delete=<?= $c['id'] ?>" class="btn btn-sm btn-outline-danger btn-confirm-delete" data-name="<?= htmlspecialchars($c['name']) ?>" title="Xóa danh mục">
                                                <i class="fa-solid fa-trash"></i>
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-outline-secondary" disabled title="Không thể xóa vì còn sản phẩm"><i class="fa-solid fa-lock"></i></button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
