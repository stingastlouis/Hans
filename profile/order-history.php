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