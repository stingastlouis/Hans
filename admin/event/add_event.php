<?php
include '../../configs/db.php';
include '../../configs/timezoneConfigs.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['event_name'];
    $description = $_POST['event_description'];
    $price = $_POST['event_price'];
    $discount_price = $_POST['event_discount_price'];

    // Handle file upload
    if (!empty($_FILES['event_image']['name'])) {
        $upload_dir = '../../assets/uploads/';
        $file_name = basename($_FILES['event_image']['name']);
        $target_file = $upload_dir . $file_name;

        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = mime_content_type($_FILES['event_image']['tmp_name']);

        if (in_array($file_type, $allowed_types)) {
            if (move_uploaded_file($_FILES['event_image']['tmp_name'], $target_file)) {
                try {
                    // Start a transaction
                    $conn->beginTransaction();

                    // Insert the event into the Event table
                    $stmt = $conn->prepare("INSERT INTO Event (Name, Description, Price, DiscountPrice, ImagePath, DateCreated) 
                                            VALUES (?, ?, ?, ?, ?, NOW())");
                    $stmt->execute([$name, $description, $price, $discount_price, $file_name]);

                    if ($stmt->rowCount() > 0) {
                        $eventId = $conn->lastInsertId();

                        // Fetch 'ACTIVE' status ID
                        $statusStmt = $conn->prepare("SELECT Id FROM Status WHERE Name = 'ACTIVE' LIMIT 1");
                        $statusStmt->execute();
                        $statusRow = $statusStmt->fetch(PDO::FETCH_ASSOC);

                        if ($statusRow) {
                            $statusId = $statusRow['Id'];

                            // Insert the 'ACTIVE' status for the event
                            $statusInsertStmt = $conn->prepare("INSERT INTO eventstatus (eventid, statusid, datecreated) 
                                                                VALUES (?, ?, NOW())");
                            $statusInsertStmt->execute([$eventId, $statusId]);

                            // Insert selected products and quantities into EventProducts table
                            if (isset($_POST['product_ids']) && !empty($_POST['product_ids'])) {
                                $productIds = $_POST['product_ids'];

                                // Prepare the statement for inserting into EventProducts table
                                $eventProductStmt = $conn->prepare("INSERT INTO EventProducts (EventId, ProductId, Quantity) 
                                                                    VALUES (?, ?, ?)");

                                // Loop through each selected product and its quantity
                                foreach ($productIds as $productId) {
                                    $quantityKey = "quantity_" . $productId;
                                    $quantity = isset($_POST[$quantityKey]) ? (int)$_POST[$quantityKey] : 1; // Default quantity is 1

                                    $eventProductStmt->execute([$eventId, $productId, $quantity]);
                                }
                            }

                            // Commit the transaction
                            $conn->commit();

                            // Redirect to the event page with success flag
                            header('Location: ../event.php?success=1');
                            exit;
                        } else {
                            throw new Exception("Error: 'ACTIVE' status not found.");
                        }
                    } else {
                        throw new Exception("Error: Unable to insert the event into the database.");
                    }
                } catch (Exception $e) {
                    // Rollback in case of any errors
                    $conn->rollBack();
                    echo "Error: " . $e->getMessage();
                }
            } else {
                echo "Error: Unable to upload the file.";
            }
        } else {
            echo "Error: Only JPEG, PNG, and GIF files are allowed.";
        }
    } else {
        echo "Error: Please upload an image.";
    }
}
?>
