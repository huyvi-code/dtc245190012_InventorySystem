<?php
$page_title = "Chỉnh sửa Sản phẩm";
require_once __DIR__ . '/includes/header.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id || !$pdo) {
    set_flash('danger', 'Không tìm thấy sản phẩm yêu cầu.');
    header("Location: /products.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id");
$stmt->execute([':id' => $id]);
$product = $stmt->fetch();

if (!$product) {
    set_flash('danger', 'Sản phẩm không tồn tại trong hệ thống.');
    header("Location: /products.php");
    exit;
}

$cat_stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $cat_stmt->fetchAll();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sku = clean_input($_POST['sku'] ?? '');
    $name = clean_input($_POST['name'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $unit = clean_input($_POST['unit'] ?? 'Cái');
    $purchase_price = (float)($_POST['purchase_price'] ?? 0);
    $selling_price = (float)($_POST['selling_price'] ?? 0);
    $min_quantity = (int)($_POST['min_quantity'] ?? 5);
    $location = clean_input($_POST['location'] ?? '');

    if (empty($sku)) $errors[] = "Vui lòng nhập mã SKU.";
    if (empty($name)) $errors[] = "Vui lòng nhập tên sản phẩm.";
    if (empty($category_id)) $errors[] = "Vui lòng chọn danh mục.";

    // Check unique SKU excluding current product
    $sku_check = $pdo->prepare("SELECT COUNT(*) FROM products WHERE sku = :sku AND id != :id");
    $sku_check->execute([':sku' => $sku, ':id' => $id]);
    if ($sku_check->fetchColumn() > 0) {
        $errors[] = "Mã SKU này đã được dùng bởi một sản phẩm khác.";
    }

    if (empty($errors)) {
        $update = $pdo->prepare("UPDATE products SET 
            sku = :sku, 
            name = :name, 
            category_id = :category_id, 
            unit = :unit, 
            purchase_price = :purchase_price, 
            selling_price = :selling_price, 
            min_quantity = :min_quantity, 
            location = :location 
            WHERE id = :id");
        $update->execute([
            ':sku' => $sku,
            ':name' => $name,
            ':category_id' => $category_id,
            ':unit' => $unit,
            ':purchase_price' => $purchase_price,
            ':selling_price' => $selling_price,
            ':min_quantity' => $min_quantity,
            ':location' => $location,
            ':id' => $id
        ]);

        set_flash('success', "Cập nhật sản phẩm [{$name}] thành công!");
        header("Location: /products.php");
        exit;
    }
} else {
    $sku = $product['sku'];
    $name = $product['name'];
    $category_id = $product['category_id'];
    $unit = $product['unit'];
    $purchase_price = $product['purchase_price'];
    $selling_price = $product['selling_price'];
    $min_quantity = $product['min_quantity'];
    $location = $product['location'];
}
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 text-primary"><i class="fa-solid fa-pen-to-square me-2"></i>Chỉnh sửa sản phẩm: <?= htmlspecialchars($product['name']) ?></h5>
                <a href="/products.php" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i>Quay lại</a>
            </div>
            <div class="card-body p-4">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger" role="alert">
                        <ul class="mb-0 ps-3">
                            <?php foreach ($errors as $err): ?>
                                <li><?= htmlspecialchars($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="/product_edit.php?id=<?= $id ?>">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Mã SKU <span class="text-danger">*</span></label>
                            <input type="text" name="sku" class="form-control" value="<?= htmlspecialchars($sku) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Danh mục <span class="text-danger">*</span></label>
                            <select name="category_id" class="form-select" required>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= ($category_id == $cat['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Tên sản phẩm <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($name) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Đơn vị tính</label>
                            <input type="text" name="unit" class="form-control" value="<?= htmlspecialchars($unit) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Giá nhập (VNĐ)</label>
                            <input type="number" step="1000" name="purchase_price" class="form-control" value="<?= htmlspecialchars($purchase_price) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Giá bán (VNĐ)</label>
                            <input type="number" step="1000" name="selling_price" class="form-control" value="<?= htmlspecialchars($selling_price) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Định mức tồn tối thiểu</label>
                            <input type="number" name="min_quantity" class="form-control" value="<?= htmlspecialchars($min_quantity) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Vị trí kho</label>
                            <input type="text" name="location" class="form-control" value="<?= htmlspecialchars($location) ?>">
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-top d-flex justify-content-end gap-2">
                        <a href="/products.php" class="btn btn-secondary">Hủy bỏ</a>
                        <button type="submit" class="btn btn-primary px-4"><i class="fa-solid fa-save me-1"></i> Lưu thay đổi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
