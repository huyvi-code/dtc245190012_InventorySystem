<?php
$page_title = "Bảng điều khiển Tổng quan";
require_once __DIR__ . '/includes/header.php';

// Initialize stats variables
$total_products = 0;
$total_inventory_cost = 0;
$total_inventory_revenue = 0;
$low_stock_count = 0;
$categories_data = [];
$low_stock_products = [];
$recent_transactions = [];

if ($pdo) {
    // 1. KPI Stats
    $stmt = $pdo->query("SELECT 
        COUNT(*) as total_items,
        SUM(quantity * purchase_price) as total_cost,
        SUM(quantity * selling_price) as total_retail,
        SUM(CASE WHEN quantity <= min_quantity THEN 1 ELSE 0 END) as low_count
    FROM products");
    $stats = $stmt->fetch();
    $total_products = (int)($stats['total_items'] ?? 0);
    $total_inventory_cost = (float)($stats['total_cost'] ?? 0);
    $total_inventory_revenue = (float)($stats['total_retail'] ?? 0);
    $low_stock_count = (int)($stats['low_count'] ?? 0);

    // 2. Category distribution for Chart
    $cat_stmt = $pdo->query("SELECT c.name, COUNT(p.id) as product_count, SUM(p.quantity) as total_qty 
        FROM categories c 
        LEFT JOIN products p ON c.id = p.category_id 
        GROUP BY c.id, c.name");
    $categories_data = $cat_stmt->fetchAll();

    // 3. Low stock products list
    $low_stmt = $pdo->query("SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.quantity <= p.min_quantity 
        ORDER BY p.quantity ASC LIMIT 6");
    $low_stock_products = $low_stmt->fetchAll();

    // 4. Recent transactions
    $trans_stmt = $pdo->query("SELECT t.*, p.name as product_name, p.sku, p.unit 
        FROM inventory_transactions t 
        JOIN products p ON t.product_id = p.id 
        ORDER BY t.created_at DESC LIMIT 6");
    $recent_transactions = $trans_stmt->fetchAll();
}
?>

<div class="row g-3 mb-4">
    <!-- Card 1: Tổng sản phẩm -->
    <div class="col-xl-3 col-md-6">
        <div class="card shadow-sm h-100 border-0 border-start border-primary border-4">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small text-uppercase fw-semibold">Tổng mã sản phẩm</div>
                    <div class="fs-3 fw-bold text-primary mt-1"><?= number_format($total_products) ?></div>
                    <div class="small text-muted mt-1"><i class="fa-solid fa-boxes-packing me-1"></i>Đang theo dõi trong kho</div>
                </div>
                <div class="stat-icon bg-primary-subtle text-primary">
                    <i class="fa-solid fa-box-open fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 2: Giá trị vốn tồn kho -->
    <div class="col-xl-3 col-md-6">
        <div class="card shadow-sm h-100 border-0 border-start border-success border-4">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small text-uppercase fw-semibold">Giá trị vốn hàng tồn</div>
                    <div class="fs-4 fw-bold text-success mt-1"><?= format_currency($total_inventory_cost) ?></div>
                    <div class="small text-muted mt-1"><i class="fa-solid fa-coins me-1"></i>Tính theo giá nhập kho</div>
                </div>
                <div class="stat-icon bg-success-subtle text-success">
                    <i class="fa-solid fa-sack-dollar fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 3: Giá trị bán ước tính -->
    <div class="col-xl-3 col-md-6">
        <div class="card shadow-sm h-100 border-0 border-start border-info border-4">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small text-uppercase fw-semibold">Giá trị bán dự kiến</div>
                    <div class="fs-4 fw-bold text-info mt-1"><?= format_currency($total_inventory_revenue) ?></div>
                    <div class="small text-muted mt-1"><i class="fa-solid fa-hand-holding-dollar me-1"></i>Theo giá niêm yết</div>
                </div>
                <div class="stat-icon bg-info-subtle text-info">
                    <i class="fa-solid fa-money-bill-trend-up fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 4: Cảnh báo tồn kho thấp -->
    <div class="col-xl-3 col-md-6">
        <div class="card shadow-sm h-100 border-0 border-start border-danger border-4">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small text-uppercase fw-semibold">Cảnh báo tồn kho thấp</div>
                    <div class="fs-3 fw-bold text-danger mt-1"><?= number_format($low_stock_count) ?></div>
                    <div class="small text-danger fw-semibold mt-1"><i class="fa-solid fa-triangle-exclamation me-1"></i>Cần nhập hàng bổ sung</div>
                </div>
                <div class="stat-icon bg-danger-subtle text-danger">
                    <i class="fa-solid fa-bell fs-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Section -->
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-dark"><i class="fa-solid fa-chart-pie text-primary me-2"></i>Số lượng tồn kho theo Danh mục</h6>
            </div>
            <div class="card-body d-flex align-items-center justify-content-center" style="min-height: 280px;">
                <canvas id="categoryChart" style="max-height: 260px;"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-dark"><i class="fa-solid fa-chart-column text-success me-2"></i>Sản phẩm theo Danh mục</h6>
            </div>
            <div class="card-body d-flex align-items-center justify-content-center" style="min-height: 280px;">
                <canvas id="productCountChart" style="max-height: 260px;"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Low Stock & Recent Transactions -->
<div class="row g-3">
    <!-- Low Stock Table -->
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="m-0 fw-bold text-danger"><i class="fa-solid fa-triangle-exclamation me-2"></i>Cảnh báo Tồn kho sắp hết</h6>
                <a href="/products.php?status=low" class="btn btn-sm btn-outline-danger">Xem tất cả</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Mã SKU</th>
                            <th>Tên sản phẩm</th>
                            <th class="text-center">Số lượng</th>
                            <th class="text-center">Định mức</th>
                            <th class="text-end">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($low_stock_products)): ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted"><i class="fa-solid fa-check-circle text-success me-2"></i>Tồn kho đầy đủ, không có cảnh báo nào!</td></tr>
                        <?php else: ?>
                            <?php foreach ($low_stock_products as $p): ?>
                                <tr>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($p['sku']) ?></span></td>
                                    <td class="fw-semibold"><?= htmlspecialchars($p['name']) ?></td>
                                    <td class="text-center">
                                        <span class="badge badge-low-stock px-2 py-1">
                                            <?= $p['quantity'] ?> <?= htmlspecialchars($p['unit']) ?>
                                        </span>
                                    </td>
                                    <td class="text-center text-muted small"><?= $p['min_quantity'] ?></td>
                                    <td class="text-end">
                                        <a href="/transactions.php?type=IN&product_id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary" title="Nhập thêm hàng">
                                            <i class="fa-solid fa-plus me-1"></i>Nhập kho
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="m-0 fw-bold text-dark"><i class="fa-solid fa-clock-rotate-left text-info me-2"></i>Giao dịch Nhập / Xuất gần nhất</h6>
                <a href="/transactions.php" class="btn btn-sm btn-outline-secondary">Lịch sử đầy đủ</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Thời gian</th>
                            <th>Loại</th>
                            <th>Sản phẩm</th>
                            <th class="text-center">Số lượng</th>
                            <th>Mã phiếu</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_transactions)): ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">Chưa có giao dịch nào được ghi nhận.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recent_transactions as $t): ?>
                                <tr>
                                    <td class="small text-muted"><?= format_datetime($t['created_at']) ?></td>
                                    <td>
                                        <?php if ($t['type'] === 'IN'): ?>
                                            <span class="badge bg-success"><i class="fa-solid fa-arrow-down me-1"></i>Nhập</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark"><i class="fa-solid fa-arrow-up me-1"></i>Xuất</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="fw-semibold small"><?= htmlspecialchars($t['product_name']) ?></td>
                                    <td class="text-center fw-bold <?= ($t['type'] === 'IN') ? 'text-success' : 'text-warning' ?>">
                                        <?= ($t['type'] === 'IN' ? '+' : '-') . $t['quantity'] ?> <?= htmlspecialchars($t['unit']) ?>
                                    </td>
                                    <td><span class="badge bg-light text-secondary border small"><?= htmlspecialchars($t['reference_code']) ?></span></td>
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
document.addEventListener('DOMContentLoaded', function () {
    // Data passed from PHP
    const catLabels = <?= json_encode(array_column($categories_data, 'name')) ?>;
    const catQty = <?= json_encode(array_column($categories_data, 'total_qty')) ?>;
    const catCount = <?= json_encode(array_column($categories_data, 'product_count')) ?>;

    const colors = ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#6f42c1'];

    // 1. Doughnut Chart for Quantity
    const ctx1 = document.getElementById('categoryChart');
    if (ctx1 && catLabels.length > 0) {
        new Chart(ctx1, {
            type: 'doughnut',
            data: {
                labels: catLabels,
                datasets: [{
                    data: catQty,
                    backgroundColor: colors,
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }

    // 2. Bar Chart for Products count
    const ctx2 = document.getElementById('productCountChart');
    if (ctx2 && catLabels.length > 0) {
        new Chart(ctx2, {
            type: 'bar',
            data: {
                labels: catLabels,
                datasets: [{
                    label: 'Số mã sản phẩm',
                    data: catCount,
                    backgroundColor: '#4e73df',
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } }
                }
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
