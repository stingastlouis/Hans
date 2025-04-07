<?php
include './configs/db.php';
session_start();

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["success" => false, "message" => "Invalid JSON data"]);
    exit();
}

$customerId = $_SESSION['customerId'];
$paymentMethodId = $data['paymentMethodId'];
$cartItems = $data['cartItems'];
$transactionId = $data['transactionId'];
$amount = $data['amount'];
$_SESSION['orderSuccess'] = true;

$taxRate = 0.15;  
$tax = $amount * $taxRate;
$totalAmount = $amount + $tax;

$query = "INSERT INTO `Order` (CustomerId, PaymentMethodId, Tax, TotalAmount, DateCreated) 
          VALUES (:customerId, :paymentMethodId, :tax, :totalAmount, NOW())";
$stmt = $conn->prepare($query);
$stmt->bindValue(':customerId', $customerId, PDO::PARAM_INT);
$stmt->bindValue(':paymentMethodId', $paymentMethodId, PDO::PARAM_INT);
$stmt->bindValue(':tax', $tax, PDO::PARAM_STR);
$stmt->bindValue(':totalAmount', $totalAmount, PDO::PARAM_STR);

if ($stmt->execute()) {
    $orderId = $conn->lastInsertId(); 
    
    foreach ($cartItems as $item) {
        $itemType = $item['type'];
        $productId = $item['id'];
        $quantity = $item['quantity'];
        $unitPrice = $item['price'];
        $subtotal = $unitPrice * $quantity;

        $query = "INSERT INTO `OrderItem` (OrderId, ProductId, Quantity, UnitPrice, Subtotal, DateCreated, OrderType) 
                  VALUES (:orderId, :productId, :quantity, :unitPrice, :subtotal, NOW(), :type)";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':orderId', $orderId, PDO::PARAM_INT);
        $stmt->bindValue(':productId', $productId, PDO::PARAM_INT);
        $stmt->bindValue(':quantity', $quantity, PDO::PARAM_INT);
        $stmt->bindValue(':unitPrice', $unitPrice, PDO::PARAM_STR);
        $stmt->bindValue(':subtotal', $subtotal, PDO::PARAM_STR);
        $stmt->bindValue(':type', $itemType, PDO::PARAM_STR);
        $stmt->execute();

        if ($itemType === 'product') {
            $checkStockQuery = "SELECT Stock FROM `Products` WHERE Id = :productId";
            $checkStockStmt = $conn->prepare($checkStockQuery);
            $checkStockStmt->bindValue(':productId', $productId, PDO::PARAM_INT);
            $checkStockStmt->execute();
            $productStock = $checkStockStmt->fetch(PDO::FETCH_ASSOC)['Stock'];

            if ($productStock >= $quantity) {
                $updateStockQuery = "UPDATE `Products` SET Stock = Stock - :quantity WHERE Id = :productId";
                $updateStockStmt = $conn->prepare($updateStockQuery);
                $updateStockStmt->bindValue(':quantity', $quantity, PDO::PARAM_INT);
                $updateStockStmt->bindValue(':productId', $productId, PDO::PARAM_INT);
                $updateStockStmt->execute();
            } else {
                echo json_encode(['success' => false, 'message' => 'Not enough stock for product']);
                exit();
            }
        } elseif ($itemType === 'event') {
            $eventId = $item['eventId'];

            $eventProductsQuery = "SELECT ProductId, Quantity FROM `EventProducts` WHERE EventId = :eventId";
            $eventProductsStmt = $conn->prepare($eventProductsQuery);
            $eventProductsStmt->bindValue(':eventId', $eventId, PDO::PARAM_INT);
            $eventProductsStmt->execute();

            while ($eventProduct = $eventProductsStmt->fetch(PDO::FETCH_ASSOC)) {
                $eventProductId = $eventProduct['ProductId'];
                $eventProductQuantity = $eventProduct['Quantity'];

                $checkStockQuery = "SELECT Stock FROM `Products` WHERE Id = :productId";
                $checkStockStmt = $conn->prepare($checkStockQuery);
                $checkStockStmt->bindValue(':productId', $eventProductId, PDO::PARAM_INT);
                $checkStockStmt->execute();
                $productStock = $checkStockStmt->fetch(PDO::FETCH_ASSOC)['Stock'];

                if ($productStock >= ($eventProductQuantity * $quantity)) {
                    $updateStockQuery = "UPDATE `Products` SET Stock = Stock - :quantity WHERE Id = :productId";
                    $updateStockStmt = $conn->prepare($updateStockQuery);
                    $updateStockStmt->bindValue(':quantity', $eventProductQuantity * $quantity, PDO::PARAM_INT); // Multiply by event quantity
                    $updateStockStmt->bindValue(':productId', $eventProductId, PDO::PARAM_INT);
                    $updateStockStmt->execute();
                } else {
                    echo json_encode(['success' => false, 'message' => 'Not enough stock for product in event']);
                    exit();
                }
            }
        }
    }

    $query = "INSERT INTO `Payment` (CustomerId, OrderId, PaymentMethodId, TransactionId, Amount, DateCreated) 
              VALUES (:customerId, :orderId, :paymentMethodId, :transactionId, :amount, NOW())";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':customerId', $customerId, PDO::PARAM_INT);
    $stmt->bindValue(':orderId', $orderId, PDO::PARAM_INT);
    $stmt->bindValue(':paymentMethodId', $paymentMethodId, PDO::PARAM_INT);
    $stmt->bindValue(':transactionId', $transactionId, PDO::PARAM_STR);
    $stmt->bindValue(':amount', $amount, PDO::PARAM_STR);
    $stmt->execute();

    echo json_encode(['success' => true, 'orderId' => $orderId]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to process the order']);
}
