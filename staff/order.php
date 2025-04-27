<?php
include '../sessionManagement.php';
include '../configs/constants.php';

$role = $_SESSION['role'];
if (!in_array($role, ALLOWED_EDITOR_ROLES)) {
    header("Location: ../unauthorised.php");
    exit;
}

include '../configs/db.php';
include 'includes/header.php'; 

$success = isset($_GET["success"]) ? $_GET["success"] : null;

$itemsPerPage = 4;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max($page, 1);
$offset = ($page - 1) * $itemsPerPage;
$staffId = $_SESSION["staff_id"];

$totalStmt = $conn->query("SELECT COUNT(*) FROM `Order`");
$totalOrders = $totalStmt->fetchColumn();
$totalPages = ceil($totalOrders / $itemsPerPage);

$stmt = $conn->prepare("
    SELECT 
        o.Id AS order_id,
        o.DateCreated, 
        o.TotalAmount, 
        c.Fullname AS CustomerName, 
        (CASE WHEN oi.OrderType = 'product' THEN 'Product' ELSE 'Event' END) AS OrderType,
        s.Name AS OrderStatus,
        p.TransactionId
    FROM 
        `Order` o
    JOIN 
        Customer c ON o.CustomerId = c.Id
    LEFT JOIN 
        OrderItem oi ON o.Id = oi.OrderId
    LEFT JOIN 
        (
            SELECT os1.*
            FROM OrderStatus os1
            INNER JOIN (
                SELECT OrderId, MAX(DateCreated) AS MaxDate
                FROM OrderStatus
                GROUP BY OrderId
            ) os2 ON os1.OrderId = os2.OrderId AND os1.DateCreated = os2.MaxDate
        ) os ON o.Id = os.OrderId
    LEFT JOIN 
        Status s ON os.StatusId = s.Id
    LEFT JOIN 
        Payment p ON o.Id = p.OrderId
    GROUP BY 
        o.Id
    ORDER BY 
        o.DateCreated DESC
    LIMIT 
        :limit OFFSET :offset;
");


$stmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);


$stmt4 = $conn->prepare("SELECT * FROM Status");
$stmt4->execute();
$statuses = $stmt4->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <h2>Orders</h2>
    <table class="table table-bordered">
    <thead>
    <tr>
        <th>Order ID</th>
        <th>Customer</th>
        <th>Paypal Transaction ID</th>
        <th>Total Amount</th>
        <th>Date Created</th>
        <th>Order Status</th>  
        <?php if (in_array($role, ADMIN_ONLY_ROLE)): ?>
            <th>Actions</th>
        <?php endif; ?>
    </tr>
</thead>
<tbody>
    <?php foreach ($orders as $order) { ?>
        <tr>
            <td><?= htmlspecialchars($order['order_id']) ?></td>
            <td><?= htmlspecialchars($order['CustomerName']) ?></td>
            <td><?= htmlspecialchars($order['TransactionId']) ?></td>
            <td><?= number_format($order['TotalAmount'], 2) ?></td>
            <td><?= date('Y-m-d H:i:s', strtotime($order['DateCreated'])) ?></td>
            <td><?= htmlspecialchars($order['OrderStatus']) ?></td> 
            <?php if (in_array($role, ADMIN_ONLY_ROLE)): ?>
            <td>
            <form method="POST" action="status/add_orderStatus.php" style="display: inline;">
                                        <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                        <input type="hidden" name="staff_id" value="<?= $staffId ?>">
                                        <select name="status_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                            <option value="" disabled selected>Change Status</option>
                                            <?php foreach ($statuses as $status): ?>
                                                <option value="<?= $status['Id'] ?>"><?= htmlspecialchars($status['Name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </form>
                
                <button class="btn btn-info view-order-items-btn" 
                    data-id="<?= $order['order_id'] ?>" 
                    data-bs-toggle="modal" 
                    data-bs-target="#viewOrderItemsModal">
                    View Order Items
                </button>
            </td>
            <?php endif; ?>
        </tr>
    <?php } ?>
</tbody>

    </table>

    <nav aria-label="Page navigation">
        <ul class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++) { ?>
                <li class="page-item <?= $page == $i ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                </li>
            <?php } ?>
        </ul>
    </nav>
</div>

<script src="./utils/message.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', handleSuccessOrErrorModal);
</script>

<?php include 'includes/footer.php';  ?>


<div class="modal fade" id="viewOrderItemsModal" tabindex="-1" aria-labelledby="viewOrderItemsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="viewOrderItemsModalLabel">Order Items</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="order-items-content">
          <p>Loading...</p>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const viewOrderItemsButtons = document.querySelectorAll('.view-order-items-btn');

  viewOrderItemsButtons.forEach(button => {
    button.addEventListener('click', function () {
      const orderId = this.getAttribute('data-id');
      const orderItemsContent = document.getElementById('order-items-content');
      orderItemsContent.innerHTML = '<p>Loading...</p>';

      fetch('orderitem.php?order_id=' + orderId)
        .then(response => response.json())
        .then(data => {
          if (data.error) {
            orderItemsContent.innerHTML = `<p class="text-danger">${data.error}</p>`;
          } else if (data.message) {
            orderItemsContent.innerHTML = `<p>${data.message}</p>`;
          } else {
            let table = '<table class="table table-bordered">';
            table += '<thead><tr><th>Product</th><th>Quantity</th><th>Unit Price</th><th>Subtotal</th></tr></thead><tbody>';
            
            data.forEach(item => {
              table += `<tr>
                <td>${item.product_name}</td>
                <td>${item.Quantity}</td>
                <td>${item.UnitPrice}</td>
                <td>${item.Subtotal}</td>
              </tr>`;
            });

            table += '</tbody></table>';
            orderItemsContent.innerHTML = table;
          }
        })
        .catch(error => {
          console.error('Error fetching order items:', error);
          orderItemsContent.innerHTML = '<p class="text-danger">An error occurred while loading order items.</p>';
        });
    });
  });
});
</script>
