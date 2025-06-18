<?php
include '../../configs/db.php';
include '../../configs/timezoneConfigs.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $eventId = $_POST['event_id'] ?? null;
    if (!$eventId) {
        header('Location: ../event.php?error=no_event_id');
        exit;
    }

    $stmt = $conn->prepare("SELECT * FROM Event WHERE Id = ?");
    $stmt->execute([$eventId]);
    $existingEvent = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$existingEvent) {
        header('Location: ../event.php?error=event_not_found');
        exit;
    }

    $name = $_POST['event_name'] ?? $existingEvent['Name'];
    $description = $_POST['event_description'] ?? $existingEvent['Description'];
    $price = isset($_POST['event_price']) ? $_POST['event_price'] : $existingEvent['Price'];
    $discount_price = isset($_POST['event_discount_price']) ? $_POST['event_discount_price'] : $existingEvent['DiscountPrice'];
    $staffId = $_POST["staff_id"] ?? null;
    $dateNow = date('Y-m-d H:i:s');

    $imagePath = $existingEvent['ImagePath'];
    if (!empty($_FILES['event_image']['name'])) {
        $upload_dir = '../../assets/uploads/events/';
        $ext = pathinfo($_FILES['event_image']['name'], PATHINFO_EXTENSION);
        $imageName = uniqid('event_', true) . '.' . $ext;
        $target_file = $upload_dir . $imageName;

        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = mime_content_type($_FILES['event_image']['tmp_name']);

        if (!in_array($file_type, $allowed_types)) {
            header('Location: ../event.php?error=invalid_image_type');
            exit;
        }

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        if (!move_uploaded_file($_FILES['event_image']['tmp_name'], $target_file)) {
            header('Location: ../event.php?error=upload_failed');
            exit;
        }

        $imagePath = $imageName;
    }

    try {
        $conn->beginTransaction();
        $updateSql = "UPDATE Event SET Name = ?, Description = ?, Price = ?, DiscountPrice = ?, ImagePath = ? WHERE Id = ?";
        $stmt = $conn->prepare($updateSql);
        $stmt->execute([$name, $description, $price, $discount_price, $imagePath, $eventId]);

        if ($staffId) {
            $statusStmt = $conn->prepare("SELECT Id FROM Status WHERE Name = 'ACTIVE' LIMIT 1");
            $statusStmt->execute();
            $statusRow = $statusStmt->fetch(PDO::FETCH_ASSOC);

            if ($statusRow) {
                $statusId = $statusRow['Id'];

                // Update or insert EventStatus for this event
                $checkStatusStmt = $conn->prepare("SELECT COUNT(*) FROM EventStatus WHERE EventId = ?");
                $checkStatusStmt->execute([$eventId]);
                $exists = $checkStatusStmt->fetchColumn();

                if ($exists) {
                    $updateStatusStmt = $conn->prepare("UPDATE EventStatus SET StatusId = ?, StaffId = ?, DateCreated = ? WHERE EventId = ?");
                    $updateStatusStmt->execute([$statusId, $staffId, $dateNow, $eventId]);
                } else {
                    $insertStatusStmt = $conn->prepare("INSERT INTO EventStatus (EventId, StatusId, StaffId, DateCreated) VALUES (?, ?, ?, ?)");
                    $insertStatusStmt->execute([$eventId, $statusId, $staffId, $dateNow]);
                }
            }
        }

        $deleteProductsStmt = $conn->prepare("DELETE FROM EventProducts WHERE EventId = ?");
        $deleteProductsStmt->execute([$eventId]);

        if (!empty($_POST['product_ids']) && !empty($_POST['quantities'])) {
            $productIds = $_POST['product_ids'];
            $quantities = $_POST['quantities'];

            if (count($productIds) !== count($quantities)) {
                throw new Exception("Mismatch between product IDs and quantities.");
            }

            $eventProductStmt = $conn->prepare("INSERT INTO EventProducts (EventId, ProductId, Quantity) VALUES (?, ?, ?)");

            foreach ($productIds as $index => $productId) {
                $quantity = (int)($quantities[$index] ?? 1);
                $eventProductStmt->execute([$eventId, $productId, $quantity]);
            }
        }

        $conn->commit();
        header('Location: ../event.php?success=1');
        exit;
    } catch (Exception $e) {
        $conn->rollBack();
        header('Location: ../event.php?error=1');
        exit;
    }
} else {
    header('Location: ../event.php?error=invalid_request');
    exit;
}
