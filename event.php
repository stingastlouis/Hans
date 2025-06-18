<?php include "includes/header.php"; ?>

<div class="container py-4">
    <h1 class="text-center mb-4">Event Page</h1>
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
                    echo '<div class="col-md-4 mb-4 event-item" data-event-id="' . htmlspecialchars($event['Id']) . '" data-event-date="' . $eventDate . '">
                        <div class="card" style="height: 450px; display: flex; flex-direction: column; justify-content: space-between;">
                            <img src="./assets/uploads/events/' . htmlspecialchars($event['ImagePath']) . '" class="card-img-top" alt="' . htmlspecialchars($event['Name']) . '" style="object-fit: cover; height: 200px;">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title">' . htmlspecialchars($event['Name']) . '</h5>
                                <p class="card-text">' . htmlspecialchars($event['Description']) . '</p>
                                <p class="card-text"><strong>Price:</strong> Rs ' . number_format($event['Price'], 2) . '</p>';

                    if (!empty($eventProducts[$event['Id']])) {
                        echo '<p class="card-text"><strong>Included Products:</strong></p><ul>';
                        foreach ($eventProducts[$event['Id']] as $product) {
                            echo '<li>' . htmlspecialchars($product['name']) . ' (Qty: ' . $product['quantity'] . ')</li>';
                        }
                        echo '</ul>';
                    } else {
                        echo '<p class="card-text"><em>No associated products</em></p>';
                    }

                    echo '<div class="mt-auto">
                                <a href="event-detail.php?id=' . htmlspecialchars($event["Id"]) . '" class="text-decoration-none text-dark">
                                    ' . htmlspecialchars($event["Name"]) . '
                                </a>
                          </div>
                        </div>
                    </div>
                </div>';
                }
                echo '</div>';
            } else {
                echo '<p>No events available.</p>';
            }
        } catch (PDOException $e) {
            echo '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
        }
        ?>
    </div>

    <?php if ($totalPages > 1): ?>
        <nav>
            <ul class="pagination justify-content-center mt-4">
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