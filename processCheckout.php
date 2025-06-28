<?php
include './configs/db.php';
include 'utils/pdfUtil.php';
session_start();


$conn->beginTransaction();

$data = json_decode(file_get_contents("php://input"), true);

if ($data) {
    $paymentMethodId = $data['paymentMethodId'] ?? null;
    $cartItems = $data['cartItems'] ?? [];
    $transactionId = $data['transactionId'] ?? null;
    $totalAmount = $data['amount'] ?? 0;
    $installationRequired = $data['installationRequired'] ?? false;
    $installationDate = $data['installationDate'] ?? null;
    $latLng = $data['latLng'] ?? null;
} else {
    $paymentMethodId = $_POST['paymentMethodId'] ?? null;
    $cartItems = $_SESSION['cart'] ?? [];
    $totalAmount = $_POST['totalAmount']; // You should calculate or receive this from form or session
    $installationRequired = isset($_POST['installationRequired']);
    $installationDate = $_POST['installationDate'] ?? null;
    $lat = $_POST['lat'] ?? null;
    $lng = $_POST['lng'] ?? null;
    $latLng = ($lat && $lng) ? "$lat,$lng" : null;
}

$customerId = $_SESSION['customerId'];
// $paymentMethodId = $data['paymentMethodId'];
// $cartItems = $data['cartItems'];
// $transactionId = $data['transactionId'];
// $totalAmount = $data['amount'];
// $installationRequired = $data['installationRequired'];
// $installationDate = $data['installationDate'];
// $latLng = $data['latLng'];


$query = "INSERT INTO `Order` (CustomerId, TotalAmount, DateCreated) 
          VALUES (:customerId, :totalAmount, NOW())";
$stmt = $conn->prepare($query);
$stmt->bindValue(':customerId', $customerId, PDO::PARAM_INT);
$stmt->bindValue(':totalAmount', $totalAmount, PDO::PARAM_STR);

if ($stmt->execute()) {
    $orderId = $conn->lastInsertId();

    foreach ($cartItems as $item) {
        $itemType = $item['type'];
        $productId = $item['id'];
        $quantity = $item['quantity'];
        $unitPrice = $item['price'];
        $subtotal = $unitPrice * $quantity;
        $productId = null;
        $bundleId = null;
        $eventId = null;

        switch (strtolower($itemType)) {
            case 'product':
                $productId = $item['id'];
                break;
            case 'bundle':
                $bundleId = $item['id'];
                break;
            case 'event':
                $eventId = $item['id'];
                break;
        }


        $query = "INSERT INTO `OrderItem` 
        (OrderId, ProductId, EventId, BundleId, Quantity, UnitPrice, Subtotal, OrderType, DateCreated) 
        VALUES 
        (:orderId, :productId, :eventId, :bundleId, :quantity, :unitPrice, :subtotal, :orderType, NOW())";

        $stmt = $conn->prepare($query);
        $stmt->bindValue(':orderId', $orderId, PDO::PARAM_INT);
        $stmt->bindValue(':productId', $productId, $productId !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':eventId', $eventId, $eventId !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':bundleId', $bundleId, $bundleId !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':quantity', $quantity, PDO::PARAM_INT);
        $stmt->bindValue(':unitPrice', $unitPrice, PDO::PARAM_STR);
        $stmt->bindValue(':subtotal', $subtotal, PDO::PARAM_STR);
        $stmt->bindValue(':orderType', $itemType, PDO::PARAM_STR);
        $stmt->execute();

        $orderItemId = $conn->lastInsertId();


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
        } elseif ($itemType === 'bundle') {
            $bundleId = $item['id'];

            $bundleProductsQuery = "SELECT ProductId, Quantity FROM `BundleProducts` WHERE BundleId = :bundleId";
            $bundleProductsStmt = $conn->prepare($bundleProductsQuery);
            $bundleProductsStmt->bindValue(':bundleId', $bundleId, PDO::PARAM_INT);
            $bundleProductsStmt->execute();

            while ($bundleProduct = $bundleProductsStmt->fetch(PDO::FETCH_ASSOC)) {
                $bundleProductId = $bundleProduct['ProductId'];
                $bundleProductQuantity = $bundleProduct['Quantity'];

                $checkStockQuery = "SELECT Stock FROM `Products` WHERE Id = :productId";
                $checkStockStmt = $conn->prepare($checkStockQuery);
                $checkStockStmt->bindValue(':productId', $bundleProductId, PDO::PARAM_INT);
                $checkStockStmt->execute();
                $productStock = $checkStockStmt->fetch(PDO::FETCH_ASSOC)['Stock'];

                if ($productStock >= ($bundleProductQuantity * $quantity)) {
                    $updateStockQuery = "UPDATE `Products` SET Stock = Stock - :quantity WHERE Id = :productId";
                    $updateStockStmt = $conn->prepare($updateStockQuery);
                    $updateStockStmt->bindValue(':quantity', $bundleProductQuantity * $quantity, PDO::PARAM_INT);
                    $updateStockStmt->bindValue(':productId', $bundleProductId, PDO::PARAM_INT);
                    $updateStockStmt->execute();
                } else {
                    echo json_encode(['success' => false, 'message' => 'Not enough stock for product in bundle']);
                    exit();
                }
            }
        } elseif ($itemType === 'event') {
            $eventId = $item['id'];
            $rentalStartDate = $item['selectedDates'][0];
            $rentalEndDate = end($item['selectedDates']);

            $eventRentalQuery = "INSERT INTO EventRental (OrderItemId, RentalStartDate, RentalEndDate, DateCreated) 
                                VALUES (:orderItemId, :startDate, :endDate, NOW())";
            $stmtRental = $conn->prepare($eventRentalQuery);
            $stmtRental->bindValue(':orderItemId', $orderItemId, PDO::PARAM_INT);
            $stmtRental->bindValue(':startDate', $rentalStartDate, PDO::PARAM_STR);
            $stmtRental->bindValue(':endDate', $rentalEndDate, PDO::PARAM_STR);
            $stmtRental->execute();

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

    $query = "INSERT INTO `Payment` (CustomerId, OrderId, PaymentMethodId, DateCreated) 
              VALUES (:customerId, :orderId, :paymentMethodId, NOW())";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':customerId', $customerId, PDO::PARAM_INT);
    $stmt->bindValue(':orderId', $orderId, PDO::PARAM_INT);
    $stmt->bindValue(':paymentMethodId', $paymentMethodId, PDO::PARAM_INT);
    $stmt->execute();
    $paymentId = $conn->lastInsertId();

    $paymentMethodNameQuery = "SELECT Name FROM PaymentMethod WHERE Id = :paymentMethodId";
    $stmtMethod = $conn->prepare($paymentMethodNameQuery);
    $stmtMethod->bindValue(':paymentMethodId', $paymentMethodId, PDO::PARAM_INT);
    $stmtMethod->execute();
    $paymentMethodName = $stmtMethod->fetchColumn();

    if (strtolower($paymentMethodName) === 'paypal') {
        $paypalInsertQuery = "INSERT INTO PaypalPayment (PaymentId, TransactionId, DateCreated)
                              VALUES (:paymentId, :transactionId, NOW())";
        $stmtPaypal = $conn->prepare($paypalInsertQuery);
        $stmtPaypal->bindValue(':paymentId', $paymentId, PDO::PARAM_INT);
        $stmtPaypal->bindValue(':transactionId', $transactionId, PDO::PARAM_STR);
        $stmtPaypal->execute();
    } elseif (strtolower($paymentMethodName) === 'online payment') {
        $screenshotName = null;

        if (isset($_FILES['paymentScreenshot'])) {
            $uploadDir = './assets/uploads/payments/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $filename = uniqid('screenshot_', true) . '_' . basename($_FILES['paymentScreenshot']['name']);
            $targetFile = $uploadDir . $filename;

            if (move_uploaded_file($_FILES['paymentScreenshot']['tmp_name'], $targetFile)) {
                $screenshotName = $filename;
            }
        }

        $manualInsertQuery = "INSERT INTO ManualPayment (PaymentId, ScreenshotImage, DateCreated)
                              VALUES (:paymentId, :screenshotImage, NOW())";
        $stmtManual = $conn->prepare($manualInsertQuery);
        $stmtManual->bindValue(':paymentId', $paymentId, PDO::PARAM_INT);
        $stmtManual->bindValue(':screenshotImage', $screenshotName, PDO::PARAM_STR);
        $stmtManual->execute();
    }


    if ($installationRequired) {
        $installationQuery = "INSERT INTO `Installation` (OrderId, `Location`, InstallationCost, InstallationDate, DateCreated) 
                              VALUES (:orderId, :location, :installationCost, :installationDate, NOW())";
        $stmt = $conn->prepare($installationQuery);
        $stmt->bindValue(':orderId', $orderId, PDO::PARAM_INT);
        $stmt->bindValue(':location', $latLng, PDO::PARAM_STR);
        $stmt->bindValue(':installationCost', 20, PDO::PARAM_STR);
        $stmt->bindValue(':installationDate', $installationDate, PDO::PARAM_STR);
        $stmt->execute();
        $installationId = $conn->lastInsertId();

        $statusQuery = "SELECT Id FROM Status WHERE Name = 'PENDING' LIMIT 1";
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

    $completedStatusQuery = "SELECT Id FROM Status WHERE Name = 'PROCESSING' LIMIT 1";
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

    $externalId = generateExternalId($orderId);
    $installationCost = $installationRequired ? 20 : 0;
    $pdfPath = createPdfReceipt($conn, $orderId, $customerId,  $externalId, $paymentMethodName, $installationRequired, $installationCost);


    $receiptInsert = "INSERT INTO Receipt (OrderId, ReceiptPath, ExternalId, DateCreated) 
                  VALUES (:orderId, :pdfPath, :externalId, NOW())";
    $stmtReceipt = $conn->prepare($receiptInsert);
    $stmtReceipt->bindValue(':orderId', $orderId, PDO::PARAM_INT);
    $stmtReceipt->bindValue(':pdfPath', $pdfPath, PDO::PARAM_STR);
    $stmtReceipt->bindValue(':externalId', $externalId, PDO::PARAM_STR);
    $stmtReceipt->execute();

    $conn->commit();
    $_SESSION['orderSuccess'] = true;
    if ($data) {
        echo json_encode(['success' => true, 'orderId' => $orderId, 'paypalTransaction' => $transactionId, 'total' => $totalAmount]);
    } else {
        header("Location: ./profile.php#order-history");
        exit();
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to process the order']);
}
