<?php
$page_title = "Quản lý Sản phẩm Kho";
require_once __DIR__ . '/includes/header.php';

$search = clean_input($_GET['search'] ?? '');
$category_id = clean_input($_GET['category_id'] ?? '');
$status_filter = clean_input($_GET['status'] ?? '');

$query = "SELECT p.*, c.name as category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (p.name LIKE :search OR p.sku LIKE :search OR p.location LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($category_id)) {
    $query .= " AND p.category_id = :cat_id";
    $params[':cat_id'] = $category_id;
}

if ($status_filter === 'low') {
    $query .= " AND p.quantity <= p.min_quantity AND p.quantity > 0";
} elseif ($status_filter === 'out') {
    $query .= " AND p.quantity = 0";
} elseif ($status_filter === 'available') {
    $query .= " AND p.quantity > p.min_quantity";
}

$query .= " ORDER BY p.id DESC";

$products = [];
$categories = [];

if ($pdo) {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $products = $stmt->fetchAll();

    $cat_stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
    $categories = $cat_stmt->fetchAll();
}
?>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h4 class="fw-bold mb-1"><i class="fa-solid fa-boxes-stacked text-primary me-2"></i>Danh mục Hàng tồn kho</h4>
                <p class="text-muted small mb-0">Quản lý danh sách hàng hóa, số lượng tồn kho và định mức an toàn</p>
            </div>
            <div class="d-flex gap-2">
                <a href="/transactions.php?type=IN" class="btn btn-outline-success">
                    <i class="fa-solid fa-plus-circle me-1"></i> Nhập kho
                </a>
                <a href="/transactions.php?type=OUT" class="btn btn-outline-warning">
                    <i class="fa-solid fa-minus-circle me-1"></i> Xuất kho
                </a>
                <a href="/product_add.php" class="btn btn-primary">
                    <i class="fa-solid fa-plus me-1"></i> Thêm sản phẩm mới
                </a>
            </div>
        </div>

        <hr class="my-3">

        <!-- Search & Filter Bar -->
        <form method="GET" action="/products.php" class="row g-2">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Tìm theo tên, mã SKU hoặc vị trí kho..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select name="category_id" class="form-select">
                    <option value="">-- Tất cả danh mục --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= ($category_id == $cat['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">-- Trạng thái kho --</option>
                    <option value="available" <?= ($status_filter === 'available') ? 'selected' : '' ?>>Đủ hàng</option>
                    <option value="low" <?= ($status_filter === 'low') ? 'selected' : '' ?>>Tồn thấp (Cảnh báo)</option>
                    <option value="out" <?= ($status_filter === 'out') ? 'selected' : '' ?>>Hết hàng</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-dark w-100"><i class="fa-solid fa-filter me-1"></i> Lọc</button>
                <a href="/products.php" class="btn btn-outline-secondary" title="Đặt lại"><i class="fa-solid fa-rotate-right"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Products Table -->
<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>Mã SKU</th>
                    <th>Tên sản phẩm</th>
                    <th>Danh mục</th>
                    <th class="text-end">Giá nhập</th>
                    <th class="text-end">Giá bán</th>
                    <th class="text-center">Tồn kho</th>
                    <th>Vị trí kho</th>
                    <th class="text-center" style="width: 140px;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-inbox fs-1 mb-2"></i>
                            <div>Không tìm thấy sản phẩm nào phù hợp điều kiện lọc.</div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $i => $p): ?>
                        <tr>
                            <td class="text-muted small"><?= $i + 1 ?></td>
                            <td><span class="badge bg-light text-dark border fw-bold"><?= htmlspecialchars($p['sku']) ?></span></td>
                            <td>
                                <div class="fw-semibold text-dark"><?= htmlspecialchars($p['name']) ?></div>
                                <div class="text-muted small"><?= htmlspecialchars($p['unit']) ?> &bull; ĐM tối thiểu: <?= $p['min_quantity'] ?></div>
                            </td>
                            <td><span class="badge bg-info-subtle text-info-emphasis"><?= htmlspecialchars($p['category_name'] ?? 'Không rõ') ?></span></td>
                            <td class="text-end text-muted small"><?= format_currency($p['purchase_price']) ?></td>
                            <td class="text-end fw-bold text-dark"><?= format_currency($p['selling_price']) ?></td>
                            <td class="text-center">
                                <?php if ($p['quantity'] <= 0): ?>
                                    <span class="badge badge-out-of-stock px-2 py-1"><i class="fa-solid fa-ban me-1"></i>Hết hàng (0)</span>
                                <?php elseif ($p['quantity'] <= $p['min_quantity']): ?>
                                    <span class="badge badge-low-stock px-2 py-1" title="Dưới mức an toàn <?= $p['min_quantity'] ?>">
                                        <i class="fa-solid fa-triangle-exclamation me-1"></i><?= $p['quantity'] ?> <?= htmlspecialchars($p['unit']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-in-stock px-2 py-1">
                                        <i class="fa-solid fa-check me-1"></i><?= $p['quantity'] ?> <?= htmlspecialchars($p['unit']) ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge bg-light text-secondary border"><i class="fa-solid fa-location-dot me-1 text-danger"></i><?= htmlspecialchars($p['location'] ?: 'Chưa xếp') ?></span></td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="/product_edit.php?id=<?= $p['id'] ?>" class="btn btn-outline-secondary" title="Sửa thông tin">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                    <a href="/transactions.php?type=IN&product_id=<?= $p['id'] ?>" class="btn btn-outline-primary" title="Nhập thêm hàng">
                                        <i class="fa-solid fa-plus"></i>
                                    </a>
                                    <a href="/product_delete.php?id=<?= $p['id'] ?>" class="btn btn-outline-danger btn-confirm-delete" data-name="<?= htmlspecialchars($p['name']) ?>" title="Xóa">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white py-2 text-muted small">
        Tổng số: <strong><?= count($products) ?></strong> sản phẩm được liệt kê
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
