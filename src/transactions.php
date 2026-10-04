<?php
$page_title = "Quản lý Nhập / Xuất kho";
require_once __DIR__ . '/includes/header.php';

$type = clean_input($_GET['type'] ?? 'IN');
$default_pid = (int)($_GET['product_id'] ?? 0);
$errors = [];

// Fetch products for dropdown
$products = [];
if ($pdo) {
    $p_stmt = $pdo->query("SELECT id, sku, name, quantity, unit FROM products ORDER BY name ASC");
    $products = $p_stmt->fetchAll();
}

// Handle Transaction Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = (int)($_POST['product_id'] ?? 0);
    $trans_type = clean_input($_POST['type'] ?? 'IN');
    $quantity = (int)($_POST['quantity'] ?? 0);
    $reference_code = clean_input($_POST['reference_code'] ?? '');
    $note = clean_input($_POST['note'] ?? '');
    $performed_by = clean_input($_POST['performed_by'] ?? 'Admin');

    if ($product_id <= 0) $errors[] = "Vui lòng chọn sản phẩm.";
    if ($quantity <= 0) $errors[] = "Số lượng phải lớn hơn 0.";
    if (!in_array($trans_type, ['IN', 'OUT'])) $errors[] = "Loại giao dịch không hợp lệ.";

    if (empty($reference_code)) {
        $prefix = ($trans_type === 'IN') ? 'PNK' : 'PXK';
        $reference_code = $prefix . '-' . date('Ymd') . '-' . rand(1000, 9999);
    }

    if (empty($errors) && $pdo) {
        try {
            $pdo->beginTransaction();

            // Lock row for update
            $chk = $pdo->prepare("SELECT id, name, quantity, unit FROM products WHERE id = :id FOR UPDATE");
            $chk->execute([':id' => $product_id]);
            $prod = $chk->fetch();

            if (!$prod) {
                throw new Exception("Sản phẩm không tồn tại.");
            }

            if ($trans_type === 'OUT' && $prod['quantity'] < $quantity) {
                throw new Exception("Không đủ số lượng để xuất! Tồn kho hiện tại: {$prod['quantity']} {$prod['unit']}, yêu cầu xuất: {$quantity} {$prod['unit']}.");
            }

            // Calculate new quantity
            $new_qty = ($trans_type === 'IN') ? ($prod['quantity'] + $quantity) : ($prod['quantity'] - $quantity);

            // Update product stock
            $upd = $pdo->prepare("UPDATE products SET quantity = :new_qty, updated_at = NOW() WHERE id = :id");
            $upd->execute([':new_qty' => $new_qty, ':id' => $product_id]);

            // Insert transaction log
            $ins = $pdo->prepare("INSERT INTO inventory_transactions 
                (product_id, type, quantity, reference_code, note, performed_by) 
                VALUES (:pid, :type, :qty, :ref, :note, :by)");
            $ins->execute([
                ':pid' => $product_id,
                ':type' => $trans_type,
                ':qty' => $quantity,
                ':ref' => $reference_code,
                ':note' => $note,
                ':by' => $performed_by
            ]);

            $pdo->commit();
            $action_text = ($trans_type === 'IN') ? 'Nhập kho' : 'Xuất kho';
            set_flash('success', "Thực hiện {$action_text} thành công cho [{$prod['name']}]: {$quantity} {$prod['unit']}. Tồn mới: {$new_qty} {$prod['unit']}.");
            header("Location: /transactions.php");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = $e->getMessage();
        }
    }
}

// Fetch transaction history
$history = [];
if ($pdo) {
    $h_stmt = $pdo->query("SELECT t.*, p.name as product_name, p.sku, p.unit 
        FROM inventory_transactions t 
        JOIN products p ON t.product_id = p.id 
        ORDER BY t.created_at DESC LIMIT 50");
    $history = $h_stmt->fetchAll();
}
?>

<div class="row g-4">
    <!-- Transaction Creation Form -->
    <div class="col-lg-5">
        <div class="card shadow-sm border-0 sticky-top" style="top: 80px;">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="fw-bold mb-0 text-primary">
                    <i class="fa-solid fa-arrow-right-arrow-left me-2"></i>Tạo Phiếu Nhập / Xuất kho
                </h5>
            </div>
            <div class="card-body p-4">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger" role="alert">
                        <ul class="mb-0 ps-3">
                            <?php foreach ($errors as $e): ?>
                                <li><?= htmlspecialchars($e) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="/transactions.php">
                    <!-- Type Selection -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Loại giao dịch <span class="text-danger">*</span></label>
                        <div class="row g-2">
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="type" id="typeIN" value="IN" <?= ($type === 'IN') ? 'checked' : '' ?> onchange="updateRefCode('IN')">
                                <label class="btn btn-outline-success w-100 py-2 fw-semibold" for="typeIN">
                                    <i class="fa-solid fa-arrow-down me-1"></i> Nhập kho
                                </label>
                            </div>
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="type" id="typeOUT" value="OUT" <?= ($type === 'OUT') ? 'checked' : '' ?> onchange="updateRefCode('OUT')">
                                <label class="btn btn-outline-warning w-100 py-2 fw-semibold text-dark" for="typeOUT">
                                    <i class="fa-solid fa-arrow-up me-1"></i> Xuất kho
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Product Select -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Sản phẩm <span class="text-danger">*</span></label>
                        <select name="product_id" id="productSelect" class="form-select" required>
                            <option value="">-- Chọn sản phẩm --</option>
                            <?php foreach ($products as $p): ?>
                                <option value="<?= $p['id'] ?>" <?= ($default_pid == $p['id']) ? 'selected' : '' ?>>
                                    [<?= htmlspecialchars($p['sku']) ?>] <?= htmlspecialchars($p['name']) ?> (Tồn: <?= $p['quantity'] ?> <?= htmlspecialchars($p['unit']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Quantity & Reference -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Số lượng <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" min="1" class="form-control" placeholder="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Mã chứng từ / Phiếu</label>
                            <input type="text" name="reference_code" id="refCodeInput" class="form-control" placeholder="Tự động tạo nếu để trống">
                        </div>
                    </div>

                    <!-- Performed By & Note -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Người thực hiện</label>
                        <input type="text" name="performed_by" class="form-control" value="Quản trị viên">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Ghi chú lý do</label>
                        <textarea name="note" class="form-control" rows="2" placeholder="VD: Nhập hàng nhà cung cấp, xuất bán lẻ..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                        <i class="fa-solid fa-check me-1"></i> Xác nhận Giao dịch
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Transaction History Table -->
    <div class="col-lg-7">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-history text-secondary me-2"></i>Nhật ký Giao dịch Gần đây</h5>
                <span class="badge bg-secondary"><?= count($history) ?> bản ghi</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Thời gian</th>
                            <th>Mã phiếu</th>
                            <th>Sản phẩm</th>
                            <th class="text-center">Loại</th>
                            <th class="text-end">Số lượng</th>
                            <th>Người làm</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($history)): ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">Chưa có bản ghi giao dịch nào.</td></tr>
                        <?php else: ?>
                            <?php foreach ($history as $h): ?>
                                <tr>
                                    <td class="small text-muted"><?= format_datetime($h['created_at']) ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($h['reference_code']) ?></span></td>
                                    <td>
                                        <div class="fw-semibold small"><?= htmlspecialchars($h['product_name']) ?></div>
                                        <div class="text-muted" style="font-size: 0.75rem;"><?= htmlspecialchars($h['sku']) ?></div>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($h['type'] === 'IN'): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                <i class="fa-solid fa-arrow-down me-1"></i>Nhập
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                                                <i class="fa-solid fa-arrow-up me-1"></i>Xuất
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end fw-bold <?= ($h['type'] === 'IN') ? 'text-success' : 'text-danger' ?>">
                                        <?= ($h['type'] === 'IN' ? '+' : '-') . $h['quantity'] ?> <?= htmlspecialchars($h['unit']) ?>
                                    </td>
                                    <td class="small text-muted"><?= htmlspecialchars($h['performed_by']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function updateRefCode(type) {
    const input = document.getElementById('refCodeInput');
    const dateStr = new Date().toISOString().slice(0,10).replace(/-/g, '');
    const rand = Math.floor(1000 + Math.random() * 9000);
    input.placeholder = (type === 'IN' ? 'PNK-' : 'PXK-') + dateStr + '-' + rand;
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
