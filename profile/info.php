<div class="tab-pane fade show active" id="user-info" role="tabpanel" aria-labelledby="user-info-tab">
    <?php
    include './configs/db.php';
    if (isset($_SESSION['customerId'])) {
        try {
            $stmt = $conn->prepare("
                                SELECT * FROM Customer WHERE Id = :customerId
                            ");

            $stmt->bindParam(':customerId', $_SESSION['customerId'], PDO::PARAM_INT);
            $stmt->execute();

            $customer = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($customer) {
                echo "<h3>User Information</h3>";
                echo "<p><strong>Name:</strong> " . htmlspecialchars($customer['Fullname']) . "</p>";
                echo "<p><strong>Email:</strong> " . htmlspecialchars($customer['Email']) . "</p>";
                echo "<p><strong>Phone:</strong> " . htmlspecialchars($customer['Phone']) . "</p>";
                echo "<p><strong>Address:</strong> " . nl2br(htmlspecialchars($customer['Address'])) . "</p>";
                echo "<p><strong>Account Created On:</strong> " . date("F j, Y", strtotime($customer['DateCreated'])) . "</p>";
            } else {
                echo "<p>Customer not found or session expired.</p>";
            }
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    } else {
        echo "<p>Please log in to view your profile.</p>";
    }
    ?>

</div>