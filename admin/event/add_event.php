<?php
include '../../configs/db.php';
include '../../configs/timezoneConfigs.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['event_name'];
    $description = $_POST['event_description'];
    $price = $_POST['event_price'];
    $discount_price = $_POST['event_discount_price'];

    if (!empty($_FILES['event_image']['name'])) {
        $upload_dir = '../../assets/uploads/';
        $file_name = basename($_FILES['event_image']['name']);
        $target_file = $upload_dir . $file_name;

        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = mime_content_type($_FILES['event_image']['tmp_name']);

        if (in_array($file_type, $allowed_types)) {
            if (move_uploaded_file($_FILES['event_image']['tmp_name'], $target_file)) {
                try {
                    $conn->beginTransaction();

                    $stmt = $conn->prepare("INSERT INTO Event (Name, Description, Price, DiscountPrice, ImagePath, DateCreated) 
                                            VALUES (?, ?, ?, ?, ?, NOW())");
                    $stmt->execute([$name, $description, $price, $discount_price, $file_name]);

                    if ($stmt->rowCount() > 0) {
                        $eventId = $conn->lastInsertId();

                        $statusStmt = $conn->prepare("SELECT Id FROM Status WHERE Name = 'ACTIVE' LIMIT 1");
                        $statusStmt->execute();
                        $statusRow = $statusStmt->fetch(PDO::FETCH_ASSOC);

                        if ($statusRow) {
                            $statusId = $statusRow['Id'];

                            $statusInsertStmt = $conn->prepare("INSERT INTO eventstatus (eventid, statusid, datecreated) 
                                                                VALUES (?, ?, NOW())");
                            $statusInsertStmt->execute([$eventId, $statusId]);

                            $conn->commit();

                            header('Location: ../event.php?success=1');
                            exit;
                        } else {
                            throw new Exception("Error: 'ACTIVE' status not found.");
                        }
                    } else {
                        throw new Exception("Error: Unable to insert the event into the database.");
                    }
                } catch (Exception $e) {
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
