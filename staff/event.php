<?php
include '../sessionManagement.php';
include '../configs/constants.php';

$role = $_SESSION['role'];
if (!in_array($role, ALLOWED_EDITOR_ROLES)) {
    header("Location: ../unauthorised.php");
    exit;
}

include 'includes/header.php';
include '../configs/db.php';

$staffId = $_SESSION["staff_id"];

$limit = 10;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$totalStmt = $conn->query("SELECT COUNT(*) FROM Event");
$totalRows = $totalStmt->fetchColumn();
$totalPages = ceil($totalRows / $limit);

$stmt = $conn->prepare("
    SELECT e.*, 
           s.Name AS LatestStatus, 
           GROUP_CONCAT(CONCAT(p.Name, ' (', ep.Quantity, ')') SEPARATOR ', ') AS ProductDetails
    FROM Event e
    LEFT JOIN (
        SELECT es.EventId, MAX(es.Id) AS LatestStatusId
        FROM EventStatus es
        GROUP BY es.EventId
    ) latest_es ON e.Id = latest_es.EventId
    LEFT JOIN EventStatus es ON latest_es.LatestStatusId = es.Id
    LEFT JOIN Status s ON es.StatusId = s.Id
    LEFT JOIN EventProducts ep ON e.Id = ep.EventId
    LEFT JOIN Products p ON ep.ProductId = p.Id
    GROUP BY e.Id, s.Name
    ORDER BY e.Id DESC
    LIMIT :limit OFFSET :offset
");

$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt2 = $conn->prepare("SELECT * FROM Status WHERE Name IN ('ACTIVE','INACTIVE')");
$stmt2->execute();
$statuses = $stmt2->fetchAll(PDO::FETCH_ASSOC);

$productQuery = $conn->prepare("SELECT Id, Name FROM Products");
$productQuery->execute();
$prods = $productQuery->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <h3 class="text-dark mb-4">Events</h3>
    <div class="card shadow">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <p class="text-primary m-0 fw-bold">Event List</p>
            <?php if (in_array($role, ADMIN_ONLY_ROLE)): ?>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEventModal">Add Event</button>
            <?php endif ?>
        </div>
        <div class="card-body">
            <div class="table-responsive table mt-2" id="dataTable" role="grid">
                <table class="table my-0">
                    <thead>
                        <tr>
                            <th>Id</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Price</th>
                            <th>Discount Price</th>
                            <th>Image</th>
                            <th>Latest Status</th>
                            <th>Products</th>
                            <th>Date Created</th>
                            <?php if (in_array($role, ADMIN_ONLY_ROLE)): ?>
                                <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($events as $event): ?>
                            <tr>
                                <td><?= htmlspecialchars($event['Id']) ?></td>
                                <td><?= htmlspecialchars($event['Name']) ?></td>
                                <td><?= htmlspecialchars($event['Description']) ?></td>
                                <td><?= htmlspecialchars($event['Price']) ?></td>
                                <td><?= htmlspecialchars($event['DiscountPrice']) ?></td>
                                <td>
                                    <img src="../assets/uploads/events/<?= htmlspecialchars($event['ImagePath']) ?>" alt="<?= htmlspecialchars($event['Name']) ?>" style="width: 100px; height: auto;">
                                </td>
                                <td><?= htmlspecialchars($event['LatestStatus']) ?: 'No Status' ?></td>
                                <td><?= htmlspecialchars($event['ProductDetails']) ?: 'No products linked' ?></td>
                                <td><?= htmlspecialchars($event['DateCreated']) ?></td>
                                <?php if (in_array($role, ADMIN_ONLY_ROLE)): ?>
                                    <td>
                                        <button class='btn btn-warning btn-sm edit-event-btn'
                                            data-id='<?= $event['Id'] ?>'
                                            data-name='<?= $event['Name'] ?>'
                                            data-description='<?= $event['Description'] ?>'
                                            data-price='<?= $event['Price'] ?>'
                                            data-discount='<?= $event['DiscountPrice'] ?>'>Edit</button>
                                        <button style="font-size: 12px;" class="btn btn-danger btn-del" data-bs-toggle="modal" data-bs-target="#deleteEventModal" data-id="<?= $event['Id'] ?>">Delete</button>
                                        <form method="POST" action="status/add_eventStatus.php" style="display: inline;">
                                            <input type="hidden" name="event_id" value="<?= $event['Id'] ?>">
                                            <input type="hidden" name="staff_id" value="<?= $staffId ?>">
                                            <select name="status_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                                <option value="" disabled selected>Change Status</option>
                                                <?php foreach ($statuses as $status): ?>
                                                    <option value="<?= $status['Id'] ?>"><?= htmlspecialchars($status['Name']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </form>
                                    </td>
                                <?php endif ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <nav class="mt-3">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item<?= ($i == $page) ? ' active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
    </div>
</div>


<div class="modal fade" id="addEventModal" tabindex="-1" aria-labelledby="addEventModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addEventModalLabel">Add Event</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="event/add_event.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="staff_id" value="<?= $staffId ?>">
                    <div class="mb-3">
                        <label for="eventName" class="form-label">Event Name</label>
                        <input type="text" class="form-control" id="eventName" name="event_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="eventDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="eventDescription" name="event_description" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="eventPrice" class="form-label">Price</label>
                        <input type="number" step="0.01" class="form-control" id="eventPrice" name="event_price" required>
                    </div>
                    <div class="mb-3">
                        <label for="eventDiscountPrice" class="form-label">Discount Price</label>
                        <input type="number" step="0.01" class="form-control" id="eventDiscountPrice" name="event_discount_price">
                    </div>
                    <div class="mb-3">
                        <label for="eventImage" class="form-label">Event Image</label>
                        <input type="file" class="form-control" id="eventImage" name="event_image" accept="image/*" required>
                    </div>

                    <div class="mb-3">
                        <label for="productSelect" class="form-label">Add Product to Event</label>

                        <!-- Product selection and quantity -->
                        <div class="d-flex mb-2 align-items-center">
                            <select id="productSelect" class="form-select me-2" style="max-width: 300px;">
                                <option value="">-- Select Product --</option>
                                <?php foreach ($prods as $product): ?>
                                    <option value="<?php echo $product['Id']; ?>"><?php echo htmlspecialchars($product['Name']); ?></option>
                                <?php endforeach; ?>
                            </select>

                            <input type="number" id="productQuantity" class="form-control me-2" style="width: 100px;" min="1" value="1" placeholder="Quantity">
                            <button type="button" class="btn btn-primary" onclick="addProduct()">Add</button>
                        </div>

                        <div id="addedProductsList"></div>
                    </div>

                    <script>
                        const products = <?php echo json_encode($prods); ?>;
                        const addedProducts = new Map();

                        function addProduct() {
                            const select = document.getElementById('productSelect');
                            const quantity = document.getElementById('productQuantity').value;
                            const productId = select.value;
                            const productName = select.options[select.selectedIndex]?.text;

                            if (!productId || quantity < 1) {
                                alert("Please select a product and a valid quantity.");
                                return;
                            }

                            if (addedProducts.has(productId)) {
                                alert("Product already added.");
                                return;
                            }

                            addedProducts.set(productId, quantity);
                            const container = document.getElementById('addedProductsList');

                            const wrapper = document.createElement('div');
                            wrapper.className = 'd-flex align-items-center mb-2';
                            wrapper.id = `product_row_${productId}`;
                            wrapper.innerHTML = `
            <input type="hidden" name="product_ids[]" value="${productId}">
            <input type="hidden" name="quantities[]" value="${quantity}">
            <span class="me-2">${productName}</span>
            <span class="me-2">Qty: ${quantity}</span>
            <button type="button" class="btn btn-sm btn-danger" onclick="removeProduct('${productId}')">Remove</button>
        `;
                            container.appendChild(wrapper);
                            select.value = "";
                            document.getElementById('productQuantity').value = 1;
                        }

                        function removeProduct(productId) {
                            addedProducts.delete(productId);
                            const row = document.getElementById(`product_row_${productId}`);
                            if (row) row.remove();
                        }
                    </script>


                    <button type="submit" class="btn btn-primary">Add Event</button>
                </form>
            </div>
        </div>
    </div>
</div>



<div class="modal fade" id="deleteEventModal" tabindex="-1" aria-labelledby="deleteEventModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteEventModalLabel">Delete Event</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this event?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="event/delete_event.php" method="POST">
                    <input type="hidden" id="eventIdToDelete" name="event_id">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', handleSuccessOrErrorModal);
</script>

<?php include 'includes/footer.php'; ?>

<script>
    var deleteButtons = document.querySelectorAll('.btn-del');
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            var eventId = this.getAttribute('data-id');
            document.getElementById('eventIdToDelete').value = eventId;
        });
    });
</script>



<div class="modal fade" id="editEventModal" tabindex="-1" aria-labelledby="editEventModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editEventForm" action="event/edit_event.php" method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="editEventModalLabel">Edit Event</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="event_id" id="editEventId">
                    <input type="hidden" name="staff_id" value="<?= $staffId ?>">

                    <div class="mb-3">
                        <label class="form-label">Event Name</label>
                        <input type="text" class="form-control" id="editEventName" name="event_name" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" id="editEventDescription" name="event_description" rows="3" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Price</label>
                        <input type="number" step="0.01" class="form-control" id="editEventPrice" name="event_price" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Discount Price</label>
                        <input type="number" step="0.01" class="form-control" id="editEventDiscountPrice" name="event_discount_price">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Replace Image</label>
                        <input type="file" class="form-control" name="event_image" accept="image/*">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Edit Products</label>
                        <div class="d-flex mb-2 align-items-center">
                            <select id="editProductSelect" class="form-select me-2" style="max-width: 300px;">
                                <option value="">-- Select Product --</option>
                                <?php foreach ($prods as $product): ?>
                                    <option value="<?= $product['Id'] ?>"><?= htmlspecialchars($product['Name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="number" id="editProductQuantity" class="form-control me-2" style="width: 100px;" min="1" value="1">
                            <button type="button" class="btn btn-primary" onclick="addEditProduct()">Add</button>
                        </div>

                        <div id="editAddedProductsList"></div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Update Event</button>
                </div>
            </form>
        </div>
    </div>
</div>


<script>
    const editProducts = new Map();

    function addEditProduct() {
        const select = document.getElementById('editProductSelect');
        const quantity = document.getElementById('editProductQuantity').value;
        const productId = select.value;
        const productName = select.options[select.selectedIndex]?.text;

        if (!productId || quantity < 1) {
            alert("Please select a product and a valid quantity.");
            return;
        }

        if (editProducts.has(productId)) {
            alert("Product already added.");
            return;
        }

        editProducts.set(productId, quantity);

        const container = document.getElementById('editAddedProductsList');
        const wrapper = document.createElement('div');
        wrapper.className = 'd-flex align-items-center mb-2';
        wrapper.id = `edit_product_row_${productId}`;
        wrapper.innerHTML = `
            <input type="hidden" name="product_ids[]" value="${productId}">
            <input type="hidden" name="quantities[]" value="${quantity}">
            <span class="me-2">${productName}</span>
            <span class="me-2">Qty: ${quantity}</span>
            <button type="button" class="btn btn-sm btn-danger" onclick="removeEditProduct('${productId}')">Remove</button>
        `;
        container.appendChild(wrapper);

        select.value = "";
        document.getElementById('editProductQuantity').value = 1;
    }

    function removeEditProduct(productId) {
        editProducts.delete(productId);
        document.getElementById(`edit_product_row_${productId}`).remove();
    }

    document.querySelectorAll('.edit-event-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.getAttribute('data-id');
            const name = btn.getAttribute('data-name');
            const desc = btn.getAttribute('data-description');
            const price = btn.getAttribute('data-price');
            const discount = btn.getAttribute('data-discount');

            document.getElementById('editEventId').value = id;
            document.getElementById('editEventName').value = name;
            document.getElementById('editEventDescription').value = desc;
            document.getElementById('editEventPrice').value = price;
            document.getElementById('editEventDiscountPrice').value = discount;

            document.getElementById('editAddedProductsList').innerHTML = '';
            editProducts.clear();
            fetch(`event/fetch_event_products.php?event_id=${id}`)
                .then(res => res.json())
                .then(data => {
                    data.forEach(prod => {
                        editProducts.set(prod.ProductId, prod.Quantity);

                        const wrapper = document.createElement('div');
                        wrapper.className = 'd-flex align-items-center mb-2';
                        wrapper.id = `edit_product_row_${prod.ProductId}`;
                        wrapper.innerHTML = `
                            <input type="hidden" name="product_ids[]" value="${prod.ProductId}">
                            <input type="hidden" name="quantities[]" value="${prod.Quantity}">
                            <span class="me-2">${prod.Name}</span>
                            <span class="me-2">Qty: ${prod.Quantity}</span>
                            <button type="button" class="btn btn-sm btn-danger" onclick="removeEditProduct('${prod.ProductId}')">Remove</button>
                        `;
                        document.getElementById('editAddedProductsList').appendChild(wrapper);
                    });
                });

            const modal = new bootstrap.Modal(document.getElementById('editEventModal'));
            modal.show();
        });
    });
</script>