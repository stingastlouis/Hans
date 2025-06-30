<div class="tab-pane fade" id="order-history" role="tabpanel" aria-labelledby="order-history-tab">
    <h3>Order History</h3>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>#</th>
                <th>Order Date</th>
                <th>Receipt</th>
                <th>Total</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if (isset($_SESSION['customerId'])) {
                try {
                    $ordersPerPage = 10;
                    $currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
                    $offset = ($currentPage - 1) * $ordersPerPage;

                    $countStmt = $conn->prepare("SELECT COUNT(*) FROM `Order` WHERE CustomerId = :customerId");
                    $countStmt->bindParam(':customerId', $_SESSION['customerId'], PDO::PARAM_INT);
                    $countStmt->execute();
                    $totalOrders = $countStmt->fetchColumn();
                    $totalPages = ceil($totalOrders / $ordersPerPage);

                    $orderStmt = $conn->prepare("
                    SELECT o.Id AS OrderId, o.TotalAmount, o.DateCreated, 
                           s.Name AS StatusName,
                           r.ReceiptPath
                    FROM `Order` o
                    LEFT JOIN OrderStatus os ON o.Id = os.OrderId
                    LEFT JOIN Status s ON os.StatusId = s.Id
                    LEFT JOIN Receipt r ON o.Id = r.OrderId
                    WHERE o.CustomerId = :customerId
                    AND (
                        os.Id = (
                            SELECT MAX(os_inner.Id)
                            FROM OrderStatus os_inner
                            WHERE os_inner.OrderId = o.Id
                        ) OR os.Id IS NULL
                    )
                    GROUP BY o.Id
                    ORDER BY o.DateCreated DESC
                    LIMIT :limit OFFSET :offset
                ");
                    $orderStmt->bindParam(':customerId', $_SESSION['customerId'], PDO::PARAM_INT);
                    $orderStmt->bindValue(':limit', $ordersPerPage, PDO::PARAM_INT);
                    $orderStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
                    $orderStmt->execute();
                    $orders = $orderStmt->fetchAll(PDO::FETCH_ASSOC);

                    if (!empty($orders)) {
                        $orderCount = 1 + $offset;

                        foreach ($orders as $order) {
                            $orderDate = (new DateTime($order['DateCreated']))->format('Y-m-d');
                            $totalAmount = $order['TotalAmount'];
                            $status = $order['StatusName'] ?? 'Not Available';
                            $receiptPath = $order['ReceiptPath'];

                            $receiptButton = $receiptPath
                                ? "<a href='{$receiptPath}' target='_blank' class='btn btn-sm btn-primary'>View Receipt</a>"
                                : "<span class='text-muted'>Not Available</span>";

                            echo "
                            <tr>
                                <td>{$orderCount}</td>
                                <td>{$orderDate}</td>
                                <td>{$receiptButton}</td>
                                <td>$ " . number_format($totalAmount, 2) . "</td>
                                <td>{$status}</td>
                            </tr>
                        ";
                            $orderCount++;
                        }
                    } else {
                        echo '<tr><td colspan="5" class="text-center">No orders found.</td></tr>';
                    }
                } catch (PDOException $e) {
                    echo "<tr><td colspan='5'>Error: " . $e->getMessage() . "</td></tr>";
                }
            }
            ?>
        </tbody>
    </table>

    <?php if (!empty($totalPages) && $totalPages > 1): ?>
        <nav>
            <ul class="pagination justify-content-center">
                <?php for ($page = 1; $page <= $totalPages; $page++): ?>
                    <li class="page-item <?= $page == $currentPage ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $page ?>#order-history"><?= $page ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>