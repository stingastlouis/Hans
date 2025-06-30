<?php
include "includes/header.php";
include './configs/db.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<div class='container py-4'><div class='alert alert-danger'>Invalid Event ID</div></div>";
    include "includes/footer.php";
    exit;
}

$eventId = intval($_GET['id']);

try {
    $stmt = $conn->prepare("SELECT e.*, s.Name AS StatusName
                            FROM Event e
                            LEFT JOIN EventStatus es ON e.Id = es.EventId
                            LEFT JOIN Status s ON es.StatusId = s.Id
                            WHERE es.Id = (
                                SELECT MAX(es_inner.Id) FROM EventStatus es_inner WHERE es_inner.EventId = e.Id
                            ) AND e.Id = :id");
    $stmt->execute([':id' => $eventId]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        echo "<div class='container py-4'><div class='alert alert-warning'>Event not found.</div></div>";
        include "includes/footer.php";
        exit;
    }

    $productStmt = $conn->prepare("SELECT p.Name AS ProductName, p.ImagePath, ep.Quantity
                                   FROM EventProducts ep
                                   JOIN Products p ON ep.ProductId = p.Id
                                   WHERE ep.EventId = :id");
    $productStmt->execute([':id' => $eventId]);
    $products = $productStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<div class='container py-4'><div class='alert alert-danger'>Error: " . $e->getMessage() . "</div></div>";
    include "includes/footer.php";
    exit;
}
?>

<div class="container py-5">
    <div class="row">
        <div class="col-md-6">
            <img src="./assets/uploads/events/<?= htmlspecialchars($event['ImagePath']) ?>" class="img-fluid" alt="<?= htmlspecialchars($event['Name']) ?>">
        </div>
        <div class="col-md-6">
            <h2><?= htmlspecialchars($event['Name']) ?></h2>
            <p><?= htmlspecialchars($event['Description']) ?></p>
            <p><strong>Price:</strong> $ <?= number_format($event['Price'], 2) ?></p>
            <?php if ($event['DiscountPrice']): ?>
                <p><strong>Discount Price:</strong> $ <?= number_format($event['DiscountPrice'], 2) ?></p>
            <?php endif; ?>

            <?php if (!empty($products)): ?>
                <h5 class="mt-4">Included Products</h5>
                <ul class="list-unstyled">
                    <?php foreach ($products as $product): ?>
                        <li class="d-flex align-items-center mb-2">
                            <img src="./assets/uploads/events/<?= htmlspecialchars($event['ImagePath']) ?>"
                                alt="<?= htmlspecialchars($event['Name']) ?>"
                                style="width: 40px; height: 40px; object-fit: cover; margin-right: 10px; border-radius: 5px;">
                            <span>
                                <?= htmlspecialchars($product['ProductName']) ?> (Qty: <?= $product['Quantity'] ?>)
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p><em>No products associated with this event.</em></p>
            <?php endif; ?>

            <div class="d-flex justify-content-between align-items-start mt-4 flex-wrap">
                <div class="me-3 mb-3">
                    <h5 class="mb-2">Rental Period</h5>
                    <div class="d-flex flex-column flex-sm-row gap-2">
                        <div>
                            <label for="rental-start" class="form-label mb-1">Start Date</label>
                            <input type="date" id="rental-start" class="form-control form-control-sm" style="width: 130px;" required>
                        </div>
                        <div>
                            <label for="rental-end" class="form-label mb-1">End Date</label>
                            <input type="date" id="rental-end" class="form-control form-control-sm" style="width: 130px;" readonly required>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-3">
                <p class="mb-1"><strong>Days:</strong> <span id="rental-days">0</span></p>
                <p><strong>Total Price:</strong> $ <span id="rental-total">0.00</span></p>
            </div>

            <button class="btn btn-outline-primary mt-auto align-self-start mt-2 add-to-event-cart"
                data-id="<?= $event['Id'] ?>"
                data-name="<?= htmlspecialchars($event['Name']) ?>"
                data-price="<?= $event['DiscountPrice'] ?: $event['Price'] ?>"
                data-type="event">Add to Cart</button>



        </div>
    </div>
</div>
<script src="./cart/cart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const startInput = document.getElementById("rental-start");
        const endInput = document.getElementById("rental-end");
        const daysSpan = document.getElementById("rental-days");
        const totalSpan = document.getElementById("rental-total");
        const price = parseFloat(document.querySelector(".add-to-event-cart").getAttribute("data-price"));

        const today = new Date().toISOString().split("T")[0];
        startInput.setAttribute("min", today);

        startInput.addEventListener("change", function() {
            const startDate = new Date(this.value);
            if (!isNaN(startDate)) {
                const nextDay = new Date(startDate);
                nextDay.setDate(startDate.getDate() + 1);

                const minEnd = nextDay.toISOString().split("T")[0];
                endInput.value = "";
                endInput.setAttribute("min", minEnd);
                endInput.removeAttribute("readonly");
                updateTotals();
            }
        });

        endInput.addEventListener("change", updateTotals);

        function updateTotals() {
            const start = new Date(startInput.value);
            const end = new Date(endInput.value);

            if (!isNaN(start) && !isNaN(end) && end > start) {
                const days = Math.ceil((end - start) / (1000 * 60 * 60 * 24));
                daysSpan.textContent = days;
                totalSpan.textContent = (days * price).toFixed(2);
            } else {
                daysSpan.textContent = "0";
                totalSpan.textContent = "0.00";
            }
        }

        function getDatesBetween(startDateStr, endDateStr) {
            const dates = [];
            let currentDate = new Date(startDateStr);
            const endDate = new Date(endDateStr);
            currentDate.setHours(0, 0, 0, 0);
            endDate.setHours(0, 0, 0, 0);

            while (currentDate <= endDate) {
                dates.push(currentDate.toISOString().split("T")[0]);
                currentDate.setDate(currentDate.getDate() + 1);
            }
            return dates;
        }

        document.querySelectorAll(".add-to-event-cart").forEach((button) => {
            button.addEventListener("click", function() {
                const id = this.getAttribute("data-id");
                const name = this.getAttribute("data-name");
                const type = this.getAttribute("data-type");
                const quantity = 1;

                const start = startInput.value;
                const end = endInput.value;
                const days = parseInt(daysSpan.textContent);
                const total = parseFloat(totalSpan.textContent);

                if (!start || !end || days <= 0) {
                    alert("Please select a valid rental period.");
                    return;
                }

                const selectedDates = getDatesBetween(start, end);

                addEventToCart(id, name, price, selectedDates, type);
                window.location.href = "event.php";
            });
        });
    });
</script>



<?php include "includes/footer.php"; ?>