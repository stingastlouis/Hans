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
$installationRequired = $data['installationRequired'];
$latLng = $data['latLng'];
$_SESSION['orderSuccess'] = true;

$taxRate = 0.15;
$tax = $amount * $taxRate;
$totalAmount = $amount + $tax;

// Insert into Order
$query = "INSERT INTO `Order` (CustomerId, PaymentMethodId, Tax, TotalAmount, DateCreated) 
          VALUES (:customerId, :paymentMethodId, :tax, :totalAmount, NOW())";
$stmt = $conn->prepare($query);
$stmt->bindValue(':customerId', $customerId, PDO::PARAM_INT);
$stmt->bindValue(':paymentMethodId', $paymentMethodId, PDO::PARAM_INT);
$stmt->bindValue(':tax', $tax, PDO::PARAM_STR);
$stmt->bindValue(':totalAmount', $totalAmount, PDO::PARAM_STR);

if ($stmt->execute()) {
    $orderId = $conn->lastInsertId();

    // Insert each cart item
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

        // Handle stock updates
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
            $eventId = $item['id'];

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
                    $updateStockStmt->bindValue(':quantity', $eventProductQuantity * $quantity, PDO::PARAM_INT);
                    $updateStockStmt->bindValue(':productId', $eventProductId, PDO::PARAM_INT);
                    $updateStockStmt->execute();
                } else {
                    echo json_encode(['success' => false, 'message' => 'Not enough stock for product in event']);
                    exit();
                }
            }
        }
    }

    // Insert into Payment
    $query = "INSERT INTO `Payment` (CustomerId, OrderId, PaymentMethodId, TransactionId, Amount, DateCreated) 
              VALUES (:customerId, :orderId, :paymentMethodId, :transactionId, :amount, NOW())";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':customerId', $customerId, PDO::PARAM_INT);
    $stmt->bindValue(':orderId', $orderId, PDO::PARAM_INT);
    $stmt->bindValue(':paymentMethodId', $paymentMethodId, PDO::PARAM_INT);
    $stmt->bindValue(':transactionId', $transactionId, PDO::PARAM_STR);
    $stmt->bindValue(':amount', $amount, PDO::PARAM_STR);
    $stmt->execute();

    // Handle installation if required
    if ($installationRequired) {
        $installationQuery = "INSERT INTO `Installation` (OrderId, `Location`, DateCreated) 
                              VALUES (:orderId, :location, NOW())";
        $stmt = $conn->prepare($installationQuery);
        $stmt->bindValue(':orderId', $orderId, PDO::PARAM_INT);
        $stmt->bindValue(':location', $latLng, PDO::PARAM_STR);
        $stmt->execute();
        $installationId = $conn->lastInsertId();

        // Insert IN PROGRESS status for installation
        $statusQuery = "SELECT Id FROM Status WHERE Name = 'IN PROGRESS' LIMIT 1";
        $stmtStatus = $conn->prepare($statusQuery);
        $stmtStatus->execute();
        $status = $stmtStatus->fetch(PDO::FETCH_ASSOC);

        if ($status) {
            $statusId = $status['Id'];

            $installationStatusQuery = "INSERT INTO `InstallationStatus` (InstallationId, StatusId, DateCreated) 
                                        VALUES (:installationId, :statusId, NOW())";
            $stmtInstallationStatus = $conn->prepare($installationStatusQuery);
            $stmtInstallationStatus->bindValue(':installationId', $installationId, PDO::PARAM_INT);
            $stmtInstallationStatus->bindValue(':statusId', $statusId, PDO::PARAM_INT);
            $stmtInstallationStatus->execute();
        }
    }

     // After installation, add COMPLETED status for the order
     $completedStatusQuery = "SELECT Id FROM Status WHERE Name = 'COMPLETED' LIMIT 1";
     $stmtCompletedStatus = $conn->prepare($completedStatusQuery);
     $stmtCompletedStatus->execute();
     $completedStatus = $stmtCompletedStatus->fetch(PDO::FETCH_ASSOC);

     if ($completedStatus) {
         $completedStatusId = $completedStatus['Id'];

         $orderCompletedQuery = "INSERT INTO `OrderStatus` (OrderId, StatusId, DateCreated) 
                                 VALUES (:orderId, :statusId, NOW())";
         $stmtOrderCompleted = $conn->prepare($orderCompletedQuery);
         $stmtOrderCompleted->bindValue(':orderId', $orderId, PDO::PARAM_INT);
         $stmtOrderCompleted->bindValue(':statusId', $completedStatusId, PDO::PARAM_INT);
         $stmtOrderCompleted->execute();
     }

    echo json_encode(['success' => true, 'orderId' => $orderId]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to process the order']);
}
?>
