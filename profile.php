<?php include "includes/header.php"; ?>

<div class="container py-4">
    <h1 class="text-center mb-4">User Profile</h1>

    <div class="row">
        <div class="col-md-3">
            <div class="list-group" id="profileTabs" role="tablist">
                <a class="list-group-item list-group-item-action active" id="user-info-tab" data-bs-toggle="list" href="#user-info" role="tab" aria-controls="user-info">User Information</a>
                <a class="list-group-item list-group-item-action" id="order-history-tab" data-bs-toggle="list" href="#order-history" role="tab" aria-controls="order-history">Order History</a>
                <a class="list-group-item list-group-item-action" id="queries-tab" data-bs-toggle="list" href="#queries" role="tab" aria-controls="queries">Queries</a>
            </div>
        </div>

        <div class="col-md-9">
            <div class="tab-content mt-4" id="profileTabsContent">
                <div class="tab-pane fade show active" id="user-info" role="tabpanel" aria-labelledby="user-info-tab">
                <?php
include './configs/db.php';
if (isset($_SESSION['customerId'])) {
    try {
        $stmt = $conn->prepare("
            SELECT * FROM Customer WHERE Id = :customerId
        ");
        
        $stmt->bindParam(':customerId', $_SESSION['customerId'], PDO::PARAM_INT);
        $stmt->execute();
        
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($customer) {
            echo "<h3>User Information</h3>";
            echo "<p><strong>Name:</strong> " . htmlspecialchars($customer['Fullname']) . "</p>";
            echo "<p><strong>Email:</strong> " . htmlspecialchars($customer['Email']) . "</p>";
            echo "<p><strong>Phone:</strong> " . htmlspecialchars($customer['Phone']) . "</p>";
            echo "<p><strong>Address:</strong> " . nl2br(htmlspecialchars($customer['Address'])) . "</p>";
            echo "<p><strong>Account Created On:</strong> " . date("F j, Y", strtotime($customer['DateCreated'])) . "</p>";
        } else {
            echo "<p>Customer not found or session expired.</p>";
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
} else {
    echo "<p>Please log in to view your profile.</p>";
}
?>

</div>

<div class="tab-pane fade" id="order-history" role="tabpanel" aria-labelledby="order-history-tab">
    <h3>Order History</h3>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>#</th>
                <th>Order Date</th>
                <th>Items</th>
                <th>Total</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php

            if (isset($_SESSION['customerId'])) {
                try {
                    $stmt2 = $conn->prepare("
                    SELECT o.Id AS OrderId, o.TotalAmount, o.DateCreated AS OrderDate, 
                           os.StatusId, s.Name AS StatusName, 
                           oi.ProductId, p.Name AS ProductName, oi.Quantity, oi.UnitPrice, oi.Subtotal
                    FROM `Order` o
                    LEFT JOIN OrderStatus os ON o.Id = os.OrderId 
                    LEFT JOIN Status s ON os.StatusId = s.Id
                    LEFT JOIN OrderItem oi ON o.Id = oi.OrderId
                    LEFT JOIN Products p ON oi.ProductId = p.Id
                    WHERE o.CustomerId = :customerId
                    AND (
                        os.Id = (
                            SELECT MAX(os_inner.Id) 
                            FROM OrderStatus os_inner 
                            WHERE os_inner.OrderId = o.Id
                        ) OR os.Id IS NULL
                    )
                    ORDER BY o.DateCreated DESC
                    ");

                    $stmt2->bindParam(':customerId', $_SESSION['customerId'], PDO::PARAM_INT);
                    $stmt2->execute();
                    
                    $orders = $stmt2->fetchAll(PDO::FETCH_ASSOC);


                    if (!empty($orders)) {
                        $orderCount = 1;
                        $orderItems = []; 
                        $orderStatuses = []; 


                        foreach ($orders as $order) {

                            $orderItems[$order['OrderId']][] = [
                                'productName' => $order['ProductName'],
                                'quantity' => $order['Quantity']
                            ];

                            $orderStatuses[$order['OrderId']] = $order['StatusName'] ?? 'Not Available';
                        }

                        foreach ($orderItems as $orderId => $items) {
                            $itemList = '<ul>';
                            foreach ($items as $item) {
                                $itemList .= "<li>{$item['productName']} x {$item['quantity']}</li>";
                            }
                            $itemList .= '</ul>';
                            $orderDate = (new DateTime($orders[0]['OrderDate']))->format('Y-m-d');

                            $totalAmount = 0;
                            foreach ($orders as $order) {
                                if ($order['OrderId'] == $orderId) {
                                    $totalAmount = $order['TotalAmount'];
                                    break;
                                }
                            }

                            echo "
                                <tr>
                                    <td>{$orderCount}</td>
                                    <td>{$orderDate}</td>
                                    <td>{$itemList}</td>
                                    <td>Rs " . number_format($totalAmount, 2) . "</td>
                                    <td>{$orderStatuses[$orderId]}</td>
                                </tr>
                            ";

                            $orderCount++;
                        }
                    } else {
                        echo '<tr><td colspan="5" class="text-center">No orders found.</td></tr>';
                    }
                } catch (PDOException $e) {
                    echo "Error: " . $e->getMessage();
                }
            }
            ?>
        </tbody>
    </table>
</div>

<div class="tab-pane fade" id="queries" role="tabpanel" aria-labelledby="queries-tab">
                    <h3>Queries</h3>
                    <p>If you have any questions or queries, please feel free to ask below:</p>
                    <form action="submit_query.php" method="POST">
                        <div class="mb-3">
                            <label for="query" class="form-label">Your Query</label>
                            <textarea class="form-control" id="query" name="query" rows="4" placeholder="Describe your query..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Submit Query</button>
                    </form>

                    <h4 class="mt-4">Previous Queries</h4>
                    <ul class="list-group">
                        <li class="list-group-item">Query 1: Order not received</li>
                        <li class="list-group-item">Query 2: Incorrect item delivered</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include "includes/footer.php"; ?>
