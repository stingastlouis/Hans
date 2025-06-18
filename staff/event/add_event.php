<?php
include '../../configs/db.php';
include '../../configs/timezoneConfigs.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['event_name'];
    $description = $_POST['event_description'];
    $staffId = $_POST["staff_id"];
    $price = $_POST['event_price'];
    $discount_price = $_POST['event_discount_price'];

    $dateNow = date('Y-m-d H:i:s');

    if (!empty($_FILES['event_image']['name'])) {
        $upload_dir = '../../assets/uploads/events/';
        $originalName = pathinfo($_FILES['event_image']['name'], PATHINFO_FILENAME);
        $extension = pathinfo($_FILES['event_image']['name'], PATHINFO_EXTENSION);
        $uniqueName = $originalName . '_' . uniqid() . '.' . $extension;
        $target_file = $upload_dir . $uniqueName;

        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = mime_content_type($_FILES['event_image']['tmp_name']);

        if (in_array($file_type, $allowed_types)) {
            if (move_uploaded_file($_FILES['event_image']['tmp_name'], $target_file)) {
                try {
                    $conn->beginTransaction();
                    $stmt = $conn->prepare("INSERT INTO Event (Name, Description, Price, DiscountPrice, ImagePath, DateCreated) 
                                            VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$name, $description, $price, $discount_price, $uniqueName, $dateNow]);

                    if ($stmt->rowCount() > 0) {
                        $eventId = $conn->lastInsertId();
                        $statusStmt = $conn->prepare("SELECT Id FROM Status WHERE Name = 'ACTIVE' LIMIT 1");
                        $statusStmt->execute();
                        $statusRow = $statusStmt->fetch(PDO::FETCH_ASSOC);

                        if ($statusRow) {
                            $statusId = $statusRow['Id'];
                            $statusInsertStmt = $conn->prepare("INSERT INTO EventStatus (eventid, statusid, staffid, datecreated) 
                                                                VALUES (?, ?, ?, ?)");
                            $statusInsertStmt->execute([$eventId, $statusId, $staffId, $dateNow]);

                            if (isset($_POST['product_ids'], $_POST['quantities']) && !empty($_POST['product_ids']) && !empty($_POST['quantities'])) {
                                $productIds = $_POST['product_ids'];
                                $quantities = $_POST['quantities'];

                                if (count($productIds) !== count($quantities)) {
                                    throw new Exception("Mismatch between product IDs and quantities.");
                                }

                                $eventProductStmt = $conn->prepare("INSERT INTO EventProducts (EventId, ProductId, Quantity) 
                                                                    VALUES (?, ?, ?)");

                                foreach ($productIds as $index => $productId) {
                                    $quantity = isset($quantities[$index]) ? (int)$quantities[$index] : 1;
                                    $eventProductStmt->execute([$eventId, $productId, $quantity]);
                                }
                            }

                            $conn->commit();
                            header('Location: ../event.php?success=1');
                            exit;
                        } else {
                            header('Location: ../event.php?error=1');
                            exit;
                        }
                    } else {
                        header('Location: ../event.php?error=1');
                        exit;
                    }
                } catch (Exception $e) {
                    $conn->rollBack();
                    header('Location: ../event.php?error=1');
                    exit;
                }
            } else {
                header('Location: ../event.php?error=1');
                exit;
            }
        } else {
            header('Location: ../event.php?error=1');
            exit;
        }
    } else {
        header('Location: ../event.php?error=1');
        exit;
    }
}
