<?php include "includes/header.php"; ?>

<div class="container py-4">
    <h1 class="text-center mb-5 fw-bold text-primary">Event Page</h1>
    <div class="row">
        <?php
        include './configs/db.php';

        $limit = 10;
        $page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
        $offset = ($page - 1) * $limit;
        $isAdmin = isset($_SESSION['staff_id']);
        try {
            $totalStmt = $conn->prepare("SELECT COUNT(*) 
                                     FROM Event e
                                     LEFT JOIN EventStatus es ON e.Id = es.EventId
                                     LEFT JOIN Status s ON es.StatusId = s.Id
                                     WHERE es.Id = (
                                         SELECT MAX(es_inner.Id) 
                                         FROM EventStatus es_inner 
                                         WHERE es_inner.EventId = e.Id
                                     )
                                     AND LOWER(s.Name) = 'active'");
            $totalStmt->execute();
            $totalEvents = $totalStmt->fetchColumn();
            $totalPages = ceil($totalEvents / $limit);

            $stmt = $conn->prepare("SELECT e.*, 
                                       es.StatusId, 
                                       s.Name AS StatusName
                                FROM Event e
                                LEFT JOIN EventStatus es ON e.Id = es.EventId
                                LEFT JOIN Status s ON es.StatusId = s.Id
                                WHERE es.Id = (
                                    SELECT MAX(es_inner.Id) 
                                    FROM EventStatus es_inner 
                                    WHERE es_inner.EventId = e.Id
                                )
                                AND LOWER(s.Name) = 'active'
                                ORDER BY e.DateCreated DESC
                                LIMIT :limit OFFSET :offset");
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $eventProducts = [];
            $stmt = $conn->prepare("SELECT ep.EventId, p.Name AS ProductName, ep.Quantity
                                FROM EventProducts ep
                                JOIN Products p ON ep.ProductId = p.Id");
            $stmt->execute();
            $productData = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($productData as $product) {
                $eventProducts[$product['EventId']][] = [
                    'name' => $product['ProductName'],
                    'quantity' => $product['Quantity']
                ];
            }

            if (!empty($events)) {
                echo '<div class="row">';
                foreach ($events as $event) {
                    $eventDate = date('Y-m-d', strtotime($event['DateCreated']));
                    $safeTitle = htmlspecialchars($event['Name']);
                    $safeDesc = htmlspecialchars($event['Description']);
                    echo '<div class="col-md-4 mb-4 event-item" data-event-id="' . htmlspecialchars($event['Id']) . '" data-event-date="' . $eventDate . '">
                        <div class="card shadow-sm d-flex flex-column" style="height: 450px;">
                            <img src="./assets/uploads/events/' . htmlspecialchars($event['ImagePath']) . '" class="card-img-top" alt="' . $safeTitle . '" style="object-fit: cover; height: 200px; border-bottom: 1px solid #dee2e6;">
                            <div class="card-body d-flex flex-column p-3">
                                <h5 class="card-title fw-semibold text-primary mb-2 text-truncate" style="max-height: 3rem;" data-bs-toggle="tooltip" data-bs-placement="top" title="' . $safeTitle . '">' . $safeTitle . '</h5>
                                <p class="card-text text-muted mb-3 flex-grow-1 overflow-hidden" style="
                                    display: -webkit-box;
                                    -webkit-line-clamp: 4;
                                    -webkit-box-orient: vertical;
                                    text-overflow: ellipsis;
                                    max-height: 5.5rem;
                                " data-bs-toggle="tooltip" data-bs-placement="top" title="' . $safeDesc . '">' . $safeDesc . '</p>
                                <p class="card-text mb-3"><strong>Price:</strong> $ ' . number_format($event['Price'], 2) . '</p>';

                    if (!empty($eventProducts[$event['Id']])) {
                        echo '<p class="card-text fw-semibold mb-2">Included Products:</p><ul class="mb-3" style="list-style-type: disc; padding-left: 20px; max-height: 5rem; overflow-y: auto;">';
                        foreach ($eventProducts[$event['Id']] as $product) {
                            echo '<li>' . htmlspecialchars($product['name']) . ' (Qty: ' . $product['quantity'] . ')</li>';
                        }
                        echo '</ul>';
                    } else {
                        echo '<p class="card-text fst-italic mb-3 text-muted">No associated products</p>';
                    }

                    echo '<a href="event-detail.php?id=' . htmlspecialchars($event["Id"]) . '" class="btn btn-outline-primary mt-auto align-self-start">View Details</a>
                            </div>
                        </div>
                    </div>';
                }
                echo '</div>';
            } else {
                echo '<p class="text-center text-muted">No events available.</p>';
            }
        } catch (PDOException $e) {
            echo '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
        }
        ?>
    </div>

    <?php if ($totalPages > 1): ?>
        <nav>
            <ul class="pagination justify-content-center mt-5">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page - 1 ?>">Previous</a>
                    </li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
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
    <?php endif; ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })
    });
</script>


<?php include "includes/footer.php"; ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.addEventListener("click", function(event) {
            if (event.target.classList.contains("add-to-event-cart")) {
                const button = event.target;
                const id = button.getAttribute("data-id");
                const name = button.getAttribute("data-name");
                const price = parseFloat(button.getAttribute("data-price"));
                const type = button.getAttribute("data-type");
                const quantity = 1;

                addToCart(id, name, price, quantity, type);
            }
        });
    });

    document.addEventListener('DOMContentLoaded', function() {
        const cart = JSON.parse(localStorage.getItem("lightstore-cart")) || [];
        const eventItems = document.querySelectorAll('.event-item');

        cart.forEach(cartItem => {
            if (cartItem.type === 'event') {
                eventItems.forEach(eventItem => {
                    const eventId = eventItem.getAttribute('data-event-id');
                    if (eventId == cartItem.id) {
                        eventItem.style.display = 'none';
                    }
                });
            }
        });
    });
</script>