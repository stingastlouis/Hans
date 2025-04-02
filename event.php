<?php include "includes/header.php" ?>
<div class="container py-4">
    <h1 class="text-center mb-4">Event Page</h1>
    <div class="row">
    <?php
    include './configs/db.php';

    try {
        $stmt = $conn->prepare("    
            SELECT e.*, 
                   es.StatusId, 
                   s.Name AS StatusName
            FROM Events e
            LEFT JOIN EventStatus es ON e.Id = es.EventId
            LEFT JOIN Status s ON es.StatusId = s.Id
            WHERE es.Id = (
                SELECT MAX(es_inner.Id) 
                FROM EventStatus es_inner 
                WHERE es_inner.EventId = e.Id
            )
            AND LOWER(s.Name) = 'active'
            ORDER BY e.DateCreated DESC;
        ");
        $stmt->execute();
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($events)) {
            echo '<div class="row">';
            foreach ($events as $event) {
                echo ' 
                    <div class="col-md-4 mb-4">
                        <div class="card" style="height: 400px; display: flex; flex-direction: column; justify-content: space-between;">
                            <img src="./assets/uploads/' . htmlspecialchars($event['ImagePath']) . '" class="card-img-top" alt="' . htmlspecialchars($event['Name']) . '" style="object-fit: cover; height: 200px;">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title">' . htmlspecialchars($event['Name']) . '</h5>
                                <p class="card-text">' . htmlspecialchars($event['Description']) . '</p>
                                <p class="card-text"><strong>Price:</strong> Rs ' . number_format($event['Price'], 2) . '</p>
                                <div class="mt-auto">
                                    <button class="btn btn-primary add-to-cart" 
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

<div id="cart-container">
    <h4>Cart</h4>
    <ul id="cart-items" class="list-group"></ul>
    <div class="d-flex justify-content-between">
        <strong>Total:</strong>
        <span id="cart-total">Rs 0.00</span>
    </div>
    <button id="checkout-button" class="btn btn-success btn-block mt-3">Checkout</button>
</div>

<script src="./cart/cart.js"></script>

<?php include "includes/footer.php" ?>
