<?php
include '../sessionManagement.php';
include '../configs/constants.php';

$role = $_SESSION['role'];
if (!in_array($role, ALLOWED_EDITOR_ROLES)) {
    header("Location: ../unauthorised.php");
    exit;
}

include '../configs/db.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_returned'])) {
    $eventRentalId = intval($_POST['mark_returned']);

    $checkStmt = $conn->prepare("SELECT Returned FROM EventRental WHERE Id = :id");
    $checkStmt->bindValue(':id', $eventRentalId, PDO::PARAM_INT);
    $checkStmt->execute();
    $isReturned = $checkStmt->fetchColumn();

    if (!$isReturned) {
        $updateStmt = $conn->prepare("UPDATE EventRental SET Returned = TRUE WHERE Id = :id");
        $updateStmt->bindValue(':id', $eventRentalId, PDO::PARAM_INT);
        $updateStmt->execute();

        $eventIdStmt = $conn->prepare("
            SELECT e.Id AS EventId
            FROM EventRental er
            JOIN OrderItem oi ON er.OrderItemId = oi.Id
            JOIN Event e ON oi.EventId = e.Id
            WHERE er.Id = :id
        ");
        $eventIdStmt->bindValue(':id', $eventRentalId, PDO::PARAM_INT);
        $eventIdStmt->execute();
        $eventData = $eventIdStmt->fetch(PDO::FETCH_ASSOC);

        // Step 3: Restock products
        if ($eventData) {
            $eventId = $eventData['EventId'];
            $productStmt = $conn->prepare("
                SELECT ProductId, Quantity
                FROM EventProducts
                WHERE EventId = :eventId
            ");
            $productStmt->bindValue(':eventId', $eventId, PDO::PARAM_INT);
            $productStmt->execute();
            $products = $productStmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($products as $product) {
                $updateStockStmt = $conn->prepare("
                    UPDATE Products
                    SET Stock = Stock + :qty
                    WHERE Id = :productId
                ");
                $updateStockStmt->bindValue(':qty', $product['Quantity'], PDO::PARAM_INT);
                $updateStockStmt->bindValue(':productId', $product['ProductId'], PDO::PARAM_INT);
                $updateStockStmt->execute();
            }
        }

        $_SESSION['flash_message'] = "Rental #$eventRentalId marked as returned and stock updated.";
    } else {
        $_SESSION['flash_message'] = "Rental #$eventRentalId was already marked as returned. No action taken.";
    }

    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

// Fetch rentals
$orderId = $_GET['order_id'];
$stmt = $conn->prepare("
    SELECT er.Id, e.Name, er.RentalStartDate, er.RentalEndDate, er.Returned
    FROM OrderItem oi
    JOIN EventRental er ON oi.Id = er.OrderItemId
    JOIN Event e ON oi.EventId = e.Id
    WHERE oi.OrderId = :orderId AND oi.OrderType = 'event'
");
$stmt->bindValue(':orderId', $orderId, PDO::PARAM_INT);
$stmt->execute();
$rentals = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>

<head>
    <title>Event Rentals</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>

<body class="container mt-4">

    <h2 class="mb-4">Event Rentals for Order #<?= htmlspecialchars($orderId) ?></h2>

    <?php if (!empty($_SESSION['flash_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= $_SESSION['flash_message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>

    <form method="POST">
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Event Name</th>
                    <th>Rental Start</th>
                    <th>Rental End</th>
                    <th>Returned</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rentals as $rental): ?>
                    <tr>
                        <td><?= htmlspecialchars($rental['Name']) ?></td>
                        <td><?= $rental['RentalStartDate'] ?></td>
                        <td><?= $rental['RentalEndDate'] ?></td>
                        <td>
                            <?php if (!$rental['Returned']): ?>
                                <button type="submit" class="btn btn-sm btn-outline-success" name="mark_returned" value="<?= $rental['Id'] ?>">Mark as Returned</button>
                            <?php else: ?>
                                <input type="checkbox" checked disabled>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($rentals) === 0): ?>
                    <tr>
                        <td colspan="4" class="text-center">No rentals found for this order.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </form>

    <a href="order.php" class="btn btn-secondary">Back to Orders</a>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>