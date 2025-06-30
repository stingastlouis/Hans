<?php
include '../sessionManagement.php';
include '../configs/constants.php';
include './statusManagement.php';
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

$whereClauses = [];
$params = [];

if (!empty($_GET['receipt'])) {
  $whereClauses[] = "r.ExternalId LIKE :receipt";
  $params[':receipt'] = "%" . $_GET['receipt'] . "%";
}
if (!empty($_GET['transaction'])) {
  $whereClauses[] = "pp.TransactionId LIKE :transaction";
  $params[':transaction'] = "%" . $_GET['transaction'] . "%";
}
if (!empty($_GET['customer'])) {
  $whereClauses[] = "c.Fullname LIKE :customer";
  $params[':customer'] = "%" . $_GET['customer'] . "%";
}
if (!empty($_GET['order_id'])) {
  $whereClauses[] = "o.Id = :order_id";
  $params[':order_id'] = $_GET['order_id'];
}
if (!empty($_GET['status'])) {
  $whereClauses[] = "s.Name = :status";
  $params[':status'] = $_GET['status'];
}

$whereSQL = count($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

$totalStmt = $conn->prepare("SELECT COUNT(DISTINCT o.Id) FROM `Order` o 
LEFT JOIN OrderItem oi ON o.Id = oi.OrderId
LEFT JOIN Customer c ON o.CustomerId = c.Id
LEFT JOIN (
    SELECT os1.* FROM OrderStatus os1
    INNER JOIN (SELECT OrderId, MAX(DateCreated) AS MaxDate FROM OrderStatus GROUP BY OrderId) os2 
    ON os1.OrderId = os2.OrderId AND os1.DateCreated = os2.MaxDate
) os ON o.Id = os.OrderId
LEFT JOIN Status s ON os.StatusId = s.Id
LEFT JOIN Payment p ON o.Id = p.OrderId
LEFT JOIN PaypalPayment pp ON pp.PaymentId = p.Id
LEFT JOIN Receipt r ON r.OrderId = o.Id
$whereSQL");
foreach ($params as $key => $value) {
  $totalStmt->bindValue($key, $value);
}
$totalStmt->execute();
$totalOrders = $totalStmt->fetchColumn();
$totalPages = ceil($totalOrders / $itemsPerPage);

$stmt = $conn->prepare("
  SELECT 
    o.Id AS order_id,
    o.DateCreated, 
    o.TotalAmount, 
    i.Location,
    i.Id AS InstallationId,
    c.Fullname AS CustomerName, 
    s.Name AS OrderStatus,
    pp.TransactionId,
    op.ScreenShotImage,
    pm.Name AS PaymentMethod,
    r.ReceiptPath,
    r.ExternalId,
    ins_staff.Fullname AS InstallationSupervisor,
    final_installation_status.InstallationStatusName AS InstallationStatus,
    (
      SELECT COUNT(*) 
      FROM OrderItem oi2 
      WHERE oi2.OrderId = o.Id AND oi2.OrderType = 'event'
    ) AS HasEventItem
  FROM `Order` o
  JOIN Customer c ON o.CustomerId = c.Id
  LEFT JOIN OrderItem oi ON o.Id = oi.OrderId
  LEFT JOIN (
      SELECT os1.* 
      FROM OrderStatus os1
      INNER JOIN (
          SELECT OrderId, MAX(DateCreated) AS MaxDate 
          FROM OrderStatus 
          GROUP BY OrderId
      ) os2 
      ON os1.OrderId = os2.OrderId AND os1.DateCreated = os2.MaxDate
  ) os ON o.Id = os.OrderId
  LEFT JOIN Status s ON os.StatusId = s.Id
  LEFT JOIN Payment p ON o.Id = p.OrderId
  LEFT JOIN PaymentMethod pm ON p.PaymentMethodId = pm.Id
  LEFT JOIN PaypalPayment pp ON pp.PaymentId = p.Id
  LEFT JOIN ManualPayment op ON op.PaymentId = p.Id
  LEFT JOIN Receipt r ON r.OrderId = o.Id
  LEFT JOIN Installation i ON o.Id = i.OrderId
  LEFT JOIN Staff ins_staff ON i.StaffId = ins_staff.Id
  LEFT JOIN (
    SELECT latest_ins_status.OrderId, st.Name AS InstallationStatusName
    FROM (
      SELECT i.OrderId, is1.StatusId
      FROM Installation i
      INNER JOIN (
        SELECT OrderId, MAX(DateCreated) AS MaxInstallDate
        FROM Installation
        GROUP BY OrderId
      ) latest_install 
        ON i.OrderId = latest_install.OrderId 
        AND i.DateCreated = latest_install.MaxInstallDate
      INNER JOIN (
        SELECT InstallationId, MAX(DateCreated) AS MaxStatusDate
        FROM InstallationStatus
        GROUP BY InstallationId
      ) latest_status 
        ON i.Id = latest_status.InstallationId
      INNER JOIN InstallationStatus is1 
        ON is1.InstallationId = i.Id 
        AND is1.DateCreated = latest_status.MaxStatusDate
    ) latest_ins_status
    INNER JOIN Status st ON latest_ins_status.StatusId = st.Id
  ) final_installation_status ON final_installation_status.OrderId = o.Id

  $whereSQL
  GROUP BY o.Id
  ORDER BY o.DateCreated DESC
  LIMIT :limit OFFSET :offset
");



foreach ($params as $key => $value) {
  $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt4 = $conn->prepare("SELECT * FROM Status");
$stmt4->execute();
$statuses = $stmt4->fetchAll(PDO::FETCH_ASSOC);


$stmt5 = $conn->prepare("SELECT s.Id, s.Fullname
FROM Staff s
JOIN Role r ON s.RoleId = r.Id
JOIN (
    SELECT ss.StaffId, ss.StatusId
    FROM StaffStatus ss
    INNER JOIN (
        SELECT StaffId, MAX(DateCreated) AS LatestStatus
        FROM StaffStatus
        GROUP BY StaffId
    ) latest ON ss.StaffId = latest.StaffId AND ss.DateCreated = latest.LatestStatus
) latestStatus ON latestStatus.StaffId = s.Id
JOIN Status st ON latestStatus.StatusId = st.Id
WHERE r.Name = 'installer' AND st.Name = 'ACTIVE';
");
$stmt5->execute();
$installers = $stmt5->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container py-4">
  <h2 class="mb-4 text-primary"> Manage Orders</h2>

  <form method="GET" class="row g-3 align-items-end bg-light p-3 rounded shadow-sm">
    <div class="col-md-2">
      <label class="form-label">Receipt</label>
      <input type="text" name="receipt" class="form-control" value="<?= htmlspecialchars($_GET['receipt'] ?? '') ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label">PayPal Txn ID</label>
      <input type="text" name="transaction" class="form-control" value="<?= htmlspecialchars($_GET['transaction'] ?? '') ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label">Customer Name</label>
      <input type="text" name="customer" class="form-control" value="<?= htmlspecialchars($_GET['customer'] ?? '') ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label">Order ID</label>
      <input type="text" name="order_id" class="form-control" value="<?= htmlspecialchars($_GET['order_id'] ?? '') ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label">Status</label>
      <select name="status" class="form-select">
        <option value="">All Statuses</option>
        <?php foreach ($statuses as $status): ?>
          <option value="<?= $status['Name'] ?>" <?= ($_GET['status'] ?? '') === $status['Name'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($status['Name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-1">
      <button type="submit" class="btn btn-success w-100">Search</button>
    </div>
    <div class="col-md-1">
      <a href="<?= strtok($_SERVER["REQUEST_URI"], '?') ?>" class="btn btn-secondary w-100"> Reset</a>
    </div>
  </form>

  <div class="table-responsive mt-4">
    <table class="table table-hover table-bordered align-middle">
      <thead class="table-primary">
        <tr>
          <th>Order ID</th>
          <th>Customer</th>
          <th>Payment Verification</th>
          <th>Receipt</th>
          <th>Rental Dates</th>
          <th>Installation Supervisor</th>
          <th>Total</th>
          <th>Created</th>
          <th>Status</th>
          <th>Installation Status</th>
          <?php if (in_array($role, ADMIN_ONLY_ROLE)): ?>
            <th>Actions</th>
          <?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($orders as $order): ?>
          <tr>
            <td>#<?= htmlspecialchars($order['order_id']) ?></td>
            <td><?= htmlspecialchars($order['CustomerName']) ?></td>
            <td>
              <?php if (!empty($order['TransactionId'])): ?>
                <?= htmlspecialchars($order['TransactionId']) ?>
              <?php elseif (!empty($order['ScreenShotImage'])): ?>
                <a href="../assets/uploads/payments/<?= htmlspecialchars($order['ScreenShotImage']) ?>" target="_blank" class="btn btn-outline-secondary btn-sm">View Screenshot</a>
              <?php else: ?>
                <span class="text-muted">N/A</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if (!empty($order['ReceiptPath'])): ?>
                <a href="../<?= htmlspecialchars($order['ReceiptPath']) ?>" target="_blank" class="btn btn-outline-secondary btn-sm">
                  View
                </a>
              <?php else: ?>
                <span class="text-muted">No receipt</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($order['HasEventItem'] > 0): ?>
                <a href="view_rental_dates.php?order_id=<?= $order['order_id'] ?>" class="btn btn-outline-primary btn-sm">
                  View Dates
                </a>
              <?php else: ?>
                <span class="text-muted">N/A</span>
              <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($order['InstallationSupervisor'] ?? 'N/A') ?></td>
            <td>$<?= number_format($order['TotalAmount'], 2) ?></td>
            <td><?= date('Y-m-d H:i', strtotime($order['DateCreated'])) ?></td>
            <td><span class="badge bg-info text-dark"><?= htmlspecialchars($order['OrderStatus']) ?></span></td>
            <td>
              <?php if (!empty($order['InstallationStatus'])): ?>
                <span class="badge bg-secondary"><?= htmlspecialchars($order['InstallationStatus']) ?></span>
              <?php else: ?>
                <span class="text-muted">N/A</span>
              <?php endif; ?>
            </td>

            <?php if (in_array($role, ADMIN_ONLY_ROLE)): ?>
              <td>
                <?php if (
                  strtoupper($order['OrderStatus']) !== 'COMPLETED' &&
                  strtoupper($order['OrderStatus']) !== 'CANCELLED'
                ): ?>
                  <form method="POST" action="status/add_orderStatus.php" class="d-inline">
                    <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                    <input type="hidden" name="staff_id" value="<?= $staffId ?>">
                    <select name="status_id" class="form-select form-select-sm d-inline w-auto" onchange="this.form.submit()">
                      <option disabled selected>Change Order Status</option>
                      <?php foreach (getAvailableStatuses($statuses, $order['OrderStatus'], isset($order['Location'])) as $status): ?>
                        <option value="<?= $status['Id'] ?>"><?= htmlspecialchars($status['Name']) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </form>
                  <?php if (
                    !empty($order['InstallationSupervisor'])
                  ): ?>
                    <form method="POST" action="status/add_installationStatus.php" class="d-inline">
                      <input type="hidden" name="installation_id" value="<?= $order['InstallationId'] ?>">
                      <input type="hidden" name="staff_id" value="<?= $staffId ?>">
                      <select name="status_id" class="form-select form-select-sm d-inline w-auto" onchange="this.form.submit()">
                        <option disabled selected>Change Ins.. Status</option>
                        <?php foreach (getAvailableStatuses($statuses, $order['InstallationStatus'], isset($order['Location']), true) as $status): ?>
                          <option value="<?= $status['Id'] ?>"><?= htmlspecialchars($status['Name']) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </form>
                  <?php endif; ?>

                  <?php
                  if (
                    strtoupper($order['OrderStatus']) === 'READY-FOR-INSTALLATION' &&
                    isset($order['Location'])
                  ): ?>
                    <form method="POST" action="assign_installer.php" style="margin: 0;">
                      <input type="hidden" name="orderId" value="<?= $order['order_id'] ?>" />
                      <input type="hidden" name="staffId" value="<?= $staffId ?>">
                      <select name="installerId" class="form-select form-select-sm"
                        style="width: 140px; background-color: #f8f9fa; color: #333; border: 1px solid #ccc;"
                        onchange="this.form.submit()">
                        <option value="" disabled selected>Assign Supervisor</option>
                        <?php foreach ($installers as $installer): ?>
                          <option value="<?= $installer['Id'] ?>"><?= htmlspecialchars($installer['Fullname']) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </form>
                  <?php endif; ?>
                <?php endif; ?>
              </td>
            <?php endif; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <nav class="mt-4">
    <ul class="pagination justify-content-center">
      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <li class="page-item <?= $page == $i ? 'active' : '' ?>">
          <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
        </li>
      <?php endfor; ?>
    </ul>
  </nav>
</div>


<?php include 'includes/footer.php'; ?>