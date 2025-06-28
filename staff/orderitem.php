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
  SELECT 
    oi.Id,
    oi.Quantity,
    oi.UnitPrice,
    oi.Subtotal,
    oi.OrderType,
    p.Name AS ProductName,
    b.Name AS BundleName,
    e.Name AS EventName
  FROM OrderItem oi
  LEFT JOIN Products p ON oi.ProductId = p.Id
  LEFT JOIN Bundle b ON oi.BundleId = b.Id
  LEFT JOIN Event e ON oi.EventId = e.Id
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
