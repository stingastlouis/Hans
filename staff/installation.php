<?php

include '../sessionManagement.php';
include '../configs/constants.php';
include './statusManagement.php';
$role = $_SESSION['role'];
$staffId = $_SESSION["staff_id"];
if (!in_array($role, INSTALLER_ONLY_ROLE)) {
  header("Location: ../unauthorised.php");
  exit;
}

include 'includes/header.php';
include '../configs/db.php';

$userRole = $_SESSION['role'];
$staffId = $_SESSION['staff_id'];
$limit = 10;

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$query = "
  SELECT 
      i.Id AS InstallationId,
      i.OrderId,
      i.Location,
      i.InstallationDate,
      status_info.Name AS LastInstallationStatus,
      order_status_info.Name AS LastOrderStatus
  FROM 
      Installation i
  JOIN 
      Staff s ON i.StaffId = s.Id
  LEFT JOIN (
      SELECT 
          ists.InstallationId,
          st.Name,
          ists.DateCreated
      FROM 
          InstallationStatus ists
      INNER JOIN (
          SELECT InstallationId, MAX(DateCreated) AS MaxDate
          FROM InstallationStatus
          GROUP BY InstallationId
      ) latest 
          ON ists.InstallationId = latest.InstallationId 
          AND ists.DateCreated = latest.MaxDate
      INNER JOIN Status st ON ists.StatusId = st.Id
  ) AS status_info ON i.Id = status_info.InstallationId
  
  LEFT JOIN (
      SELECT 
          os.OrderId,
          st.Name,
          os.DateCreated
      FROM 
          OrderStatus os
      INNER JOIN (
          SELECT OrderId, MAX(DateCreated) AS MaxDate
          FROM OrderStatus
          GROUP BY OrderId
      ) latest_os
          ON os.OrderId = latest_os.OrderId 
          AND os.DateCreated = latest_os.MaxDate
      INNER JOIN Status st ON os.StatusId = st.Id
  ) AS order_status_info ON i.OrderId = order_status_info.OrderId
  
  WHERE 
      i.StaffId = :staffId
      AND order_status_info.Name = 'READY-FOR-INSTALLATION'
  ORDER BY 
      i.InstallationDate DESC
  LIMIT :limit OFFSET :offset
";

$stmt = $conn->prepare($query);
$stmt->bindValue(':staffId', $staffId, PDO::PARAM_INT);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$installations = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmtTotal = $conn->prepare("SELECT COUNT(*) FROM Installation WHERE StaffId = :staffId");
$stmtTotal->execute([':staffId' => $staffId]);
$totalRecords = $stmtTotal->fetchColumn();
$totalPages = ceil($totalRecords / $limit);

$stmt2 = $conn->prepare("SELECT * FROM Status WHERE Name IN ('IN-TRANSIT','INSTALLED')");
$stmt2->execute();
$statuses = $stmt2->fetchAll(PDO::FETCH_ASSOC);
$stmt2->closeCursor();
?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h4 text-primary fw-semibold mb-0">Installation List</h2>
  </div>

  <div class="table-responsive">
    <table class="table table-striped table-hover align-middle">
      <thead class="table-primary text-center">
        <tr>
          <th scope="col">Order ID</th>
          <th scope="col">Location</th>
          <th scope="col">Installation Date</th>
          <th scope="col">Status</th>
          <th scope="col">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($installations as $row): ?>
          <tr>
            <td><?= htmlspecialchars($row['OrderId']) ?></td>
            <td data-latlng="<?= htmlspecialchars($row['Location']) ?>" class="address-cell">Loading...</td>
            <td><?= htmlspecialchars($row['InstallationDate']) ?></td>
            <td><?= htmlspecialchars($row['LastInstallationStatus']) ?></td>
            <td class="text-nowrap">
              <a
                href="installation_summary.php?orderId=<?= $row['OrderId'] ?>"
                class="btn btn-sm btn-outline-warning me-1 mb-1">
                <i class="bi bi-box-seam"></i> View Items
              </a>

              <button
                type="button"
                class="btn btn-sm btn-outline-info me-1 mb-1"
                data-bs-toggle="modal"
                data-bs-target="#locationModal"
                onclick="showLocationOnMap(this)"
                data-view-location="<?= htmlspecialchars($row['Location']) ?>">
                <i class="bi bi-geo-alt-fill"></i> View Location
              </button>

              <?php if (in_array($role, ALLOWED_INSTALLATION_ROLES)): ?>
                <form method="POST" action="status/add_installationStatus.php" class="d-inline-block">
                  <input type="hidden" name="installation_id" value="<?= $row['InstallationId'] ?>">
                  <input type="hidden" name="staff_id" value="<?= $staffId ?>">
                  <select
                    name="status_id"
                    class="form-select form-select-sm d-inline w-auto"
                    onchange="this.form.submit()">
                    <option value="" disabled selected>Change Status</option>
                    <?php foreach ($statuses as $status): ?>
                      <option value="<?= $status['Id'] ?>"><?= htmlspecialchars($status['Name']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <nav aria-label="Page navigation">
    <ul class="pagination justify-content-center mt-4">
      <?php if ($page > 1): ?>
        <li class="page-item">
          <a class="page-link" href="?page=<?= $page - 1 ?>">Previous</a>
        </li>
      <?php endif; ?>

      <?php
      $start = max(1, $page - 2);
      $end = min($totalPages, $page + 2);
      for ($i = $start; $i <= $end; $i++):
      ?>
        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
          <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
        </li>
      <?php endfor; ?>

      <?php if ($page < $totalPages): ?>
        <li class="page-item">
          <a class="page-link" href="?page=<?= $page + 1 ?>">Next</a>
        </li>
      <?php endif; ?>
    </ul>
  </nav>
</div>

<div class="modal fade" id="locationModal" tabindex="-1" aria-labelledby="locationModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Installation Location</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="map" style="height: 400px; width: 100%;"></div>
        <p class="mt-3 mb-0"><strong>Address:</strong> <span id="mapAddress" class="text-muted">Loading...</span></p>
      </div>
    </div>
  </div>
</div>


<script>
  document.querySelectorAll('.address-cell').forEach(cell => {
    const latlng = cell.dataset.latlng;
    const [lat, lng] = latlng.split(',');
    fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json`)
      .then(res => res.json())
      .then(data => {
        cell.textContent = data.display_name || 'Unknown';
      })
      .catch(() => {
        cell.textContent = 'Error loading address';
      });
  });
</script>



<div class="modal fade" id="locationModal" tabindex="-1" aria-labelledby="locationModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Installation Location</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="map" style="height: 400px;"></div>
        <p class="mt-2 text-muted"><strong>Address:</strong> <span id="mapAddress">Loading...</span></p>
      </div>
    </div>
  </div>
</div>
<script>
  let map;
  let marker;

  const modal = document.getElementById('locationModal');

  modal.addEventListener('shown.bs.modal', function() {
    if (map) {
      setTimeout(() => map.invalidateSize(), 100);
    }
  });

  function showLocationOnMap(button) {
    const latlngStr = button.getAttribute('data-view-location');

    if (!latlngStr || !latlngStr.includes(',')) {
      console.error('Invalid latlng string:', latlngStr);
      document.getElementById('mapAddress').innerText = 'Invalid coordinates';
      return;
    }

    const [latStr, lngStr] = latlngStr.split(',');
    const lat = parseFloat(latStr);
    const lng = parseFloat(lngStr);

    if (isNaN(lat) || isNaN(lng)) {
      console.error('Invalid lat or lng:', lat, lng);
      document.getElementById('mapAddress').innerText = 'Invalid coordinates';
      return;
    }

    const latlng = L.latLng(lat, lng);

    if (!map) {
      map = L.map('map').setView(latlng, 13);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
      }).addTo(map);
    } else {
      map.setView(latlng, 13);
      if (marker) marker.remove();
    }

    marker = L.marker(latlng).addTo(map);
    document.getElementById('mapAddress').innerText = 'Loading...';

    fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json`)
      .then((res) => res.json())
      .then((data) => {
        document.getElementById('mapAddress').innerText =
          data.display_name || 'Address not found';
      })
      .catch((err) => {
        console.error('Geocoding failed:', err);
        document.getElementById('mapAddress').innerText =
          'Unable to fetch address';
      });
  }
</script>
<script>
  document.addEventListener('DOMContentLoaded', handleSuccessOrErrorModal);
</script>

<?php include 'includes/footer.php'; ?>