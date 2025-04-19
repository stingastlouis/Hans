<?php include 'includes/header.php'; ?>

<?php
include '../configs/db.php';

$success = isset($_GET["success"]) ? $_GET["success"] : null;
$stmt = $conn->prepare("
    SELECT e.*, 
           s.Name AS LatestStatus, 
           GROUP_CONCAT(
               CONCAT(p.Name, ' (', ep.Quantity, ')') 
               SEPARATOR ', '
           ) AS ProductDetails
    FROM Event e
    LEFT JOIN (
        SELECT es.EventId, 
               MAX(es.Id) AS LatestStatusId
        FROM EventStatus es
        GROUP BY es.EventId
    ) latest_es ON e.Id = latest_es.EventId
    LEFT JOIN EventStatus es ON latest_es.LatestStatusId = es.Id
    LEFT JOIN Status s ON es.StatusId = s.Id
    LEFT JOIN EventProducts ep ON e.Id = ep.EventId
    LEFT JOIN Products p ON ep.ProductId = p.Id
    GROUP BY e.Id, s.Name
    ORDER BY e.Id DESC
");

$stmt->execute();
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt2 = $conn->prepare("SELECT * FROM Status");
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
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEventModal">
                Add Event
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive table mt-2" id="dataTable" role="grid" aria-describedby="dataTable_info">
                <table class="table my-0" id="dataTable">
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
                            <th>Actions</th>
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
                                    <img src="../assets/uploads/<?= htmlspecialchars($event['ImagePath']) ?>" alt="<?= htmlspecialchars($event['Name']) ?>" style="width: 100px; height: auto;">
                                </td>
                                <td><?= htmlspecialchars($event['LatestStatus']) ?: 'No Status' ?></td>
                                <td>
                                    <?php 
                                    echo htmlspecialchars($event['ProductDetails']) ?: 'No products linked';
                                    ?>
                                </td>
                                <td><?= htmlspecialchars($event['DateCreated']) ?></td>                              
                                <td style="padding:10px">
                                    <button class='btn btn-warning btn-sm edit-event-btn' 
                                        data-id='<?= $event['Id'] ?>' 
                                        data-name='<?= $event['Name'] ?>' 
                                        data-description='<?= $event['Description'] ?>' 
                                        data-price='<?= $event['Price'] ?>' 
                                        data-discount='<?= $event['DiscountPrice'] ?>'>Edit</button>
                                    <button style="font-size: 12px;" class="btn btn-danger btn-del" data-bs-toggle="modal" data-bs-target="#deleteEventModal" data-id="<?= $event['Id'] ?>">Delete</button>
                                    <form method="POST" action="status/add_eventStatus.php" style="display: inline;">
                                        <input type="hidden" name="event_id" value="<?= $event['Id'] ?>">
                                        <select name="status_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                            <option value="" disabled selected>Change Status</option>
                                            <?php foreach ($statuses as $status): ?>
                                                <option value="<?= $status['Id'] ?>"><?= htmlspecialchars($status['Name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
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
                        <label for="eventProducts" class="form-label">Select Products</label>
                        <div id="eventProducts">
                            <?php foreach ($prods as $product): ?>
                                <div>
                                    <input type="checkbox" id="product_<?php echo $product['Id']; ?>" name="product_ids[]" value="<?php echo $product['Id']; ?>">
                                    <label for="product_<?php echo $product['Id']; ?>"><?php echo $product['Name']; ?></label>
                                    <input type="number" name="quantity_<?php echo $product['Id']; ?>" class="form-control form-control-sm" placeholder="Quantity" min="1" value="1" style="width: 80px; display: inline-block;">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

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
            <form id="editEventForm" method="POST" action="event/modify.php" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="editEventModalLabel">Edit Event</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="event_id" id="editEventId">
                    
                    <div class="mb-3">
                        <label for="editEventTitle" class="form-label">Name</label>
                        <input type="text" class="form-control" id="editEventTitle" name="name" required>
                    </div>

                    <div class="mb-3">
                        <label for="editEventDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="editEventDescription" name="description" rows="3" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="editEventDiscount" class="form-label">Discount</label>
                        <input type="number" class="form-control" id="editEventDiscount" name="discount" required>
                    </div>

                    <div class="mb-3">
                        <label for="editEventPrice" class="form-label">Price</label>
                        <input type="number" class="form-control" id="editEventPrice" name="price" required>
                    </div>

                    <div class="mb-3">
                        <label for="editEventImage" class="form-label">Image</label>
                        <input type="file" class="form-control" id="editEventImage" name="image">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.querySelectorAll('.edit-event-btn').forEach(button => {
        button.addEventListener('click', event => {
            const id = button.getAttribute('data-id');
            const name = button.getAttribute('data-name');
            const description = button.getAttribute('data-description');
            const discount = button.getAttribute('data-discount');
            const price = button.getAttribute('data-price');

            document.getElementById('editEventId').value = id;
            document.getElementById('editEventTitle').value = name;
            document.getElementById('editEventDescription').value = description;
            document.getElementById('editEventDiscount').value = discount;
            document.getElementById('editEventPrice').value = price;

            const modal = new bootstrap.Modal(document.getElementById('editEventModal'));
            modal.show();
        });
    });
</script>