<?php
include '../sessionManagement.php';
include '../configs/constants.php';
include '../configs/db.php';

// Ensure the user is logged in and authorized to access the page
$role = $_SESSION['role'];
if (!in_array($role, ALLOWED_EDITOR_ROLES)) {
    header("Location: ../unauthorised.php");
    exit;
}

// Get the order_id from the query parameter
$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($orderId <= 0) {
    echo json_encode(['error' => 'Invalid order ID']);
    exit;
}

// Prepare the query to fetch order items
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

// Fetch the order items
$orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check if there are any items, otherwise return a message
if ($orderItems) {
    echo json_encode($orderItems);
} else {
    echo json_encode(['message' => 'No items found for this order']);
}
?>
