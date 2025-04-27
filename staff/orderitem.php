<?php
include '../sessionManagement.php';
include '../configs/constants.php';
include '../configs/db.php';

$role = $_SESSION['role'];
if (!in_array($role, ALLOWED_EDITOR_ROLES)) {
    header("Location: ../unauthorised.php");
    exit;
}

$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($orderId <= 0) {
    echo json_encode(['error' => 'Invalid order ID']);
    exit;
}

$stmt = $conn->prepare("
    SELECT oi.Id AS order_item_id,
           p.Name AS product_name,
           oi.Quantity,
           oi.UnitPrice,
           oi.Subtotal
    FROM OrderItem oi
    LEFT JOIN Products p ON oi.ProductId = p.Id
    WHERE oi.OrderId = :orderId
");
$stmt->bindValue(':orderId', $orderId, PDO::PARAM_INT);
$stmt->execute();

$orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($orderItems) {
    echo json_encode($orderItems);
} else {
    echo json_encode(['message' => 'No items found for this order']);
}
?>
