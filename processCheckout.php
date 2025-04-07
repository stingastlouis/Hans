<?php
include './configs/db.php';
session_start();

// Get the raw POST data (PayPal sends data as JSON)
$data = json_decode(file_get_contents("php://input"), true);

// Check if the data is valid
if (!$data) {
    echo json_encode(["success" => false, "message" => "Invalid JSON data"]);
    exit();
}

// Accessing the sent data
$customerId = $_SESSION['customerId']; // Fetch from session
$paymentMethodId = $data['paymentMethodId'];
$cartItems = $data['cartItems'];
$transactionId = $data['transactionId'];
$amount = $data['amount'];
$_SESSION['orderSuccess'] = true;
// Calculate tax and total amounts
$taxRate = 0.15;  // Assuming tax rate is 15%
$tax = $amount * $taxRate;
$totalAmount = $amount + $tax;

// Insert the order into the `Order` table using PDO
$query = "INSERT INTO `Order` (CustomerId, PaymentMethodId, Tax, TotalAmount, DateCreated) 
          VALUES (:customerId, :paymentMethodId, :tax, :totalAmount, NOW())";
$stmt = $conn->prepare($query);
$stmt->bindValue(':customerId', $customerId, PDO::PARAM_INT);
$stmt->bindValue(':paymentMethodId', $paymentMethodId, PDO::PARAM_INT);
$stmt->bindValue(':tax', $tax, PDO::PARAM_STR);
$stmt->bindValue(':totalAmount', $totalAmount, PDO::PARAM_STR);

if ($stmt->execute()) {
    $orderId = $conn->lastInsertId();  // Get the generated Order ID
    
    // Insert order items into the `OrderItem` table
    foreach ($cartItems as $item) {
        $productId = $item['id']; // Assuming cart items have an 'id' field
        $quantity = $item['quantity'];
        $unitPrice = $item['price'];
        $subtotal = $unitPrice * $quantity;

        $query = "INSERT INTO `OrderItem` (OrderId, ProductId, Quantity, UnitPrice, Subtotal, DateCreated) 
                  VALUES (:orderId, :productId, :quantity, :unitPrice, :subtotal, NOW())";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':orderId', $orderId, PDO::PARAM_INT);
        $stmt->bindValue(':productId', $productId, PDO::PARAM_INT);
        $stmt->bindValue(':quantity', $quantity, PDO::PARAM_INT);
        $stmt->bindValue(':unitPrice', $unitPrice, PDO::PARAM_STR);
        $stmt->bindValue(':subtotal', $subtotal, PDO::PARAM_STR);
        $stmt->execute();
    }

    // Insert payment details into the `Payment` table using PDO
    $query = "INSERT INTO `Payment` (CustomerId, OrderId, PaymentMethodId, TransactionId, Amount, DateCreated) 
              VALUES (:customerId, :orderId, :paymentMethodId, :transactionId, :amount, NOW())";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':customerId', $customerId, PDO::PARAM_INT);
    $stmt->bindValue(':orderId', $orderId, PDO::PARAM_INT);
    $stmt->bindValue(':paymentMethodId', $paymentMethodId, PDO::PARAM_INT);
    $stmt->bindValue(':transactionId', $transactionId, PDO::PARAM_STR);
    $stmt->bindValue(':amount', $amount, PDO::PARAM_STR);
    $stmt->execute();


    // Respond with success and the generated order ID
    echo json_encode(['success' => true, 'orderId' => $orderId]);
} else {
    // Respond with failure if the query execution fails
    echo json_encode(['success' => false, 'message' => 'Failed to process the order']);
}

