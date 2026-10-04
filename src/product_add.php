<?php
$page_title = "Thêm Sản phẩm Mới";
require_once __DIR__ . '/includes/header.php';

$errors = [];
$sku = '';
$name = '';
$category_id = '';
$unit = 'Cái';
$purchase_price = 0;
$selling_price = 0;
$quantity = 0;
$min_quantity = 5;
$location = 'Kệ A1';

// Fetch categories
$categories = [];
if ($pdo) {
    $cat_stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
    $categories = $cat_stmt->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sku = clean_input($_POST['sku'] ?? '');
    $name = clean_input($_POST['name'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $unit = clean_input($_POST['unit'] ?? 'Cái');
    $purchase_price = (float)($_POST['purchase_price'] ?? 0);
    $selling_price = (float)($_POST['selling_price'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 0);
    $min_quantity = (int)($_POST['min_quantity'] ?? 5);
    $location = clean_input($_POST['location'] ?? '');

    // Validation
    if (empty($sku)) $errors[] = "Vui lòng nhập mã SKU sản phẩm.";
    if (empty($name)) $errors[] = "Vui lòng nhập tên sản phẩm.";
    if (empty($category_id)) $errors[] = "Vui lòng chọn danh mục.";
    if ($purchase_price < 0 || $selling_price < 0) $errors[] = "Giá không thể là số âm.";
    if ($quantity < 0) $errors[] = "Số lượng tồn ban đầu không thể âm.";

    if (empty($errors) && $pdo) {
        // Check SKU uniqueness
        $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE sku = :sku");
        $check_stmt->execute([':sku' => $sku]);
        if ($check_stmt->fetchColumn() > 0) {
            $errors[] = "Mã SKU '{$sku}' đã tồn tại trong hệ thống. Vui lòng chọn mã khác.";
        } else {
            $insert_stmt = $pdo->prepare("INSERT INTO products 
                (sku, name, category_id, unit, purchase_price, selling_price, quantity, min_quantity, location) 
                VALUES (:sku, :name, :category_id, :unit, :purchase_price, :selling_price, :quantity, :min_quantity, :location)");
            
            $insert_stmt->execute([
                ':sku' => $sku,
                ':name' => $name,
                ':category_id' => $category_id,
                ':unit' => $unit,
                ':purchase_price' => $purchase_price,
                ':selling_price' => $selling_price,
                ':quantity' => $quantity,
                ':min_quantity' => $min_quantity,
                ':location' => $location
            ]);

            $new_id = $pdo->lastInsertId();

            // Record initial transaction if quantity > 0
            if ($quantity > 0) {
                $ref = 'PNK-' . date('Ymd') . '-' . rand(100, 999);
                $trans_stmt = $pdo->prepare("INSERT INTO inventory_transactions 
                    (product_id, type, quantity, reference_code, note, performed_by) 
                    VALUES (:pid, 'IN', :qty, :ref, 'Tạo sản phẩm với số lượng ban đầu', 'Admin')");
                $trans_stmt->execute([
                    ':pid' => $new_id,
                    ':qty' => $quantity,
                    ':ref' => $ref
                ]);
            }

            set_flash('success', "Thêm sản phẩm [{$name}] thành công!");
            header("Location: /products.php");
            exit;
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 text-primary"><i class="fa-solid fa-box-open me-2"></i>Thêm sản phẩm mới vào kho</h5>
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

                <form method="POST" action="/product_add.php">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Mã SKU <span class="text-danger">*</span></label>
                            <input type="text" name="sku" class="form-control" placeholder="VD: LAP-DELL-XPS15" value="<?= htmlspecialchars($sku) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Danh mục <span class="text-danger">*</span></label>
                            <select name="category_id" class="form-select" required>
                                <option value="">-- Chọn danh mục --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= ($category_id == $cat['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Tên sản phẩm <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="Tên chi tiết của sản phẩm..." value="<?= htmlspecialchars($name) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Đơn vị tính</label>
                            <input type="text" name="unit" class="form-control" placeholder="Cái, Hộp, Chiếc..." value="<?= htmlspecialchars($unit) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Giá nhập kho (VNĐ)</label>
                            <input type="number" step="1000" name="purchase_price" class="form-control" value="<?= htmlspecialchars($purchase_price) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Giá niêm yết bán (VNĐ)</label>
                            <input type="number" step="1000" name="selling_price" class="form-control" value="<?= htmlspecialchars($selling_price) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Số lượng tồn ban đầu</label>
                            <input type="number" name="quantity" class="form-control" value="<?= htmlspecialchars($quantity) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Định mức an toàn tối thiểu</label>
                            <input type="number" name="min_quantity" class="form-control" value="<?= htmlspecialchars($min_quantity) ?>">
                            <small class="text-muted">Cảnh báo khi tồn kho &le; mức này</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Vị trí trong kho</label>
                            <input type="text" name="location" class="form-control" placeholder="VD: Kệ A1-02" value="<?= htmlspecialchars($location) ?>">
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-top d-flex justify-content-end gap-2">
                        <a href="/products.php" class="btn btn-secondary">Hủy bỏ</a>
                        <button type="submit" class="btn btn-primary px-4"><i class="fa-solid fa-save me-1"></i> Lưu sản phẩm</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
