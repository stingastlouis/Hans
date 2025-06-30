<?php
include '../../configs/db.php';
include '../../configs/timezoneConfigs.php';
include '../../utils/communicationUtils.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $bundleId = $_POST['bundle_id'] ?? null;
    if (!$bundleId) {
        redirectBackWithMessage('error', 'No bundle ID provided.');
    }

    $stmt = $conn->prepare("SELECT * FROM Bundle WHERE Id = ?");
    $stmt->execute([$bundleId]);
    $existingBundle = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$existingBundle) {
        redirectBackWithMessage('error', 'Bundle not found.');
    }

    $name = $_POST['bundle_name'] ?? $existingBundle['Name'];
    $description = $_POST['bundle_description'] ?? $existingBundle['Description'];
    $price = isset($_POST['bundle_price']) ? $_POST['bundle_price'] : $existingBundle['Price'];
    $discount_price = isset($_POST['bundle_discount_price']) ? $_POST['bundle_discount_price'] : $existingBundle['DiscountPrice'];
    $staffId = $_POST["staff_id"] ?? null;
    $dateNow = date('Y-m-d H:i:s');

    $imagePath = $existingBundle['ImagePath'];
    if (!empty($_FILES['bundle_image']['name'])) {
        $upload_dir = '../../assets/uploads/bundles/';
        $ext = pathinfo($_FILES['bundle_image']['name'], PATHINFO_EXTENSION);
        $imageName = uniqid('bundle_', true) . '.' . $ext;
        $target_file = $upload_dir . $imageName;

        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = mime_content_type($_FILES['bundle_image']['tmp_name']);

        if (!in_array($file_type, $allowed_types)) {
            redirectBackWithMessage('error', 'Invalid image type.');
        }

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        if (!move_uploaded_file($_FILES['bundle_image']['tmp_name'], $target_file)) {
            redirectBackWithMessage('error', 'Failed to upload image.');
        }

        $imagePath = $imageName;
    }

    try {
        $conn->beginTransaction();
        $updateSql = "UPDATE Bundle SET Name = ?, Description = ?, Price = ?, DiscountPrice = ?, ImagePath = ? WHERE Id = ?";
        $stmt = $conn->prepare($updateSql);
        $stmt->execute([$name, $description, $price, $discount_price, $imagePath, $bundleId]);

        if ($staffId) {
            $statusStmt = $conn->prepare("SELECT Id FROM Status WHERE Name = 'ACTIVE' LIMIT 1");
            $statusStmt->execute();
            $statusRow = $statusStmt->fetch(PDO::FETCH_ASSOC);

            if ($statusRow) {
                $statusId = $statusRow['Id'];

                $checkStatusStmt = $conn->prepare("SELECT COUNT(*) FROM BundleStatus WHERE BundleId = ?");
                $checkStatusStmt->execute([$bundleId]);
                $exists = $checkStatusStmt->fetchColumn();

                if ($exists) {
                    $updateStatusStmt = $conn->prepare("UPDATE BundleStatus SET StatusId = ?, StaffId = ?, DateCreated = ? WHERE BundleId = ?");
                    $updateStatusStmt->execute([$statusId, $staffId, $dateNow, $bundleId]);
                } else {
                    $insertStatusStmt = $conn->prepare("INSERT INTO BundleStatus (BundleId, StatusId, StaffId, DateCreated) VALUES (?, ?, ?, ?)");
                    $insertStatusStmt->execute([$bundleId, $statusId, $staffId, $dateNow]);
                }
            }
        }

        $deleteProductsStmt = $conn->prepare("DELETE FROM BundleProducts WHERE BundleId = ?");
        $deleteProductsStmt->execute([$bundleId]);

        if (!empty($_POST['product_ids']) && !empty($_POST['quantities'])) {
            $productIds = $_POST['product_ids'];
            $quantities = $_POST['quantities'];

            if (count($productIds) !== count($quantities)) {
                throw new Exception("Mismatch between product IDs and quantities.");
            }

            $bundleProductStmt = $conn->prepare("INSERT INTO BundleProducts (BundleId, ProductId, Quantity) VALUES (?, ?, ?)");

            foreach ($productIds as $index => $productId) {
                $quantity = (int)($quantities[$index] ?? 1);
                $bundleProductStmt->execute([$bundleId, $productId, $quantity]);
            }
        }

        $conn->commit();
        redirectBackWithMessage('success', 'Bundle successfully edited.');
    } catch (Exception $e) {
        $conn->rollBack();
        redirectBackWithMessage('error', 'An error occurred while editing the bundle.');
    }
} else {
    redirectBackWithMessage('error', 'Invalid request.');
}
