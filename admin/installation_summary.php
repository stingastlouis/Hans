<?php
include '../configs/db.php';

if (!isset($_GET['orderId'])) {
    echo "Missing Order ID.";
    exit;
}

$orderId = $_GET['orderId'];

$stmt = $conn->prepare("
    SELECT oi.*, 
           CASE 
               WHEN oi.OrderType = 'product' THEN p.Name 
               ELSE e.Name 
           END AS ItemName,
           CASE 
               WHEN oi.OrderType = 'product' THEN c.Name 
               ELSE NULL 
           END AS CategoryName
    FROM OrderItem oi
    LEFT JOIN Products p ON oi.ProductId = p.Id AND oi.OrderType = 'product'
    LEFT JOIN Categories c ON p.CategoryId = c.Id
    LEFT JOIN Event e ON oi.ProductId = e.Id AND oi.OrderType = 'event'
    WHERE oi.OrderId = ?
");
$stmt->execute([$orderId]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$items) {
    echo "<p class='text-muted'>No items found for this order.</p>";
    exit;
}
?>
<?php include 'includes/header.php'; ?>

<?php foreach ($items as $item): ?>
<div class="card mb-3 shadow-sm border-0">
    <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-start">
        <div class="d-flex flex-column flex-md-row align-items-md-center gap-3">
            <div class="text-center">
                <span class="badge bg-<?= $item['OrderType'] === 'product' ? 'primary' : 'success' ?>">
                    <?= ucfirst($item['OrderType']) ?>
                </span>
            </div>

            <div>
                <h5 class="mb-1"><?= htmlspecialchars($item['ItemName']) ?></h5>
                <?php if ($item['OrderType'] === 'product'): ?>
                    <small class="text-muted">Category: <?= htmlspecialchars($item['CategoryName']) ?></small>
                <?php endif; ?>
            </div>
        </div>

        <div class="text-end mt-3 mt-md-0">
            <div><strong>Price:</strong> Rs <?= number_format($item['UnitPrice'], 2) ?></div>
            <div><strong>Qty:</strong> <?= htmlspecialchars($item['Quantity']) ?></div>
            <div><strong>Subtotal:</strong> Rs <?= number_format($item['Subtotal'], 2) ?></div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<?php if (empty($items)): ?>
    <p class="text-muted text-center">No items found for this order.</p>
<?php endif; ?>

<style>
    .card-body {
        background-color: #fdfdfd;
    }
</style>
<?php include 'includes/footer.php'; ?>