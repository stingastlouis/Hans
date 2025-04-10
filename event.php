<?php include "includes/header.php"; ?> 
<div class="container py-4">
    <h1 class="text-center mb-4">Event Page</h1>
    <div class="row">
    <?php
    include './configs/db.php';

    try {
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
                                ORDER BY e.DateCreated DESC;");
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
                echo ' 
                    <div class="col-md-4 mb-4">
                        <div class="card" style="height: 450px; display: flex; flex-direction: column; justify-content: space-between;">
                            <img src="./assets/uploads/' . htmlspecialchars($event['ImagePath']) . '" class="card-img-top" alt="' . htmlspecialchars($event['Name']) . '" style="object-fit: cover; height: 200px;">
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

                echo '
                                <div class="mt-auto">
                                    <button class="btn btn-primary add-to-event-cart" 
                                        data-id="' . htmlspecialchars($event['Id']) . '" 
                                        data-name="' . htmlspecialchars($event['Name']) . '" 
                                        data-price="' . number_format($event['Price'], 2) . '" 
                                        data-type="event">
                                        Add to Cart
                                    </button>
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
        echo 'Error: ' . $e->getMessage();
    }
    ?>
    </div>
</div>

<?php include "includes/footer.php"; ?> 

<script>
document.addEventListener('DOMContentLoaded', function () {
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
</script>
