<?php
include '../../configs/db.php';
include '../../configs/timezoneConfigs.php';
include '../../utils/communicationUtils.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['bundle_name'];
    $description = $_POST['bundle_description'];
    $staffId = $_POST["staff_id"];
    $price = $_POST['bundle_price'];
    $discount_price = $_POST['bundle_discount_price'];
    $dateNow = date('Y-m-d H:i:s');

    if (!empty($_FILES['bundle_image']['name'])) {
        $upload_dir = '../../assets/uploads/bundles/';

        $original_name = pathinfo($_FILES['bundle_image']['name'], PATHINFO_FILENAME);
        $extension = pathinfo($_FILES['bundle_image']['name'], PATHINFO_EXTENSION);
        $unique_suffix = date('YmdHis') . '_' . bin2hex(random_bytes(5));
        $file_name = $original_name . '_' . $unique_suffix . '.' . $extension;
        $target_file = $upload_dir . $file_name;

        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = mime_content_type($_FILES['bundle_image']['tmp_name']);

        if (in_array($file_type, $allowed_types)) {
            if (move_uploaded_file($_FILES['bundle_image']['tmp_name'], $target_file)) {
                try {
                    $conn->beginTransaction();
                    $stmt = $conn->prepare("INSERT INTO Bundle (Name, Description, Price, DiscountPrice, ImagePath, DateCreated) 
                                            VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$name, $description, $price, $discount_price, $file_name, $dateNow]);

                    if ($stmt->rowCount() > 0) {
                        $bundleId = $conn->lastInsertId();
                        $statusStmt = $conn->prepare("SELECT Id FROM Status WHERE Name = 'ACTIVE' LIMIT 1");
                        $statusStmt->execute();
                        $statusRow = $statusStmt->fetch(PDO::FETCH_ASSOC);

                        if ($statusRow) {
                            $statusId = $statusRow['Id'];
                            $statusInsertStmt = $conn->prepare("INSERT INTO BundleStatus (bundleid, statusid, staffid, datecreated) 
                                                                VALUES (?, ?, ?, ?)");
                            $statusInsertStmt->execute([$bundleId, $statusId, $staffId, $dateNow]);

                            if (isset($_POST['product_ids'], $_POST['quantities']) && !empty($_POST['product_ids']) && !empty($_POST['quantities'])) {
                                $productIds = $_POST['product_ids'];
                                $quantities = $_POST['quantities'];

                                if (count($productIds) !== count($quantities)) {
                                    throw new Exception("Mismatch between product IDs and quantities.");
                                }

                                $bundleProductStmt = $conn->prepare("INSERT INTO BundleProducts (BundleId, ProductId, Quantity) 
                                                                    VALUES (?, ?, ?)");

                                foreach ($productIds as $index => $productId) {
                                    $quantity = isset($quantities[$index]) ? (int)$quantities[$index] : 1;
                                    $bundleProductStmt->execute([$bundleId, $productId, $quantity]);
                                }
                            }

                            $conn->commit();
                            redirectBackWithMessage('success', 'Bundle successfully created.');
                        } else {
                            redirectBackWithMessage('error', 'Failed to find active status.');
                        }
                    } else {
                        redirectBackWithMessage('error', 'Failed to insert bundle.');
                    }
                } catch (Exception $e) {
                    $conn->rollBack();
                    redirectBackWithMessage('error', 'An unexpected error occurred: ' . $e->getMessage());
                }
            } else {
                redirectBackWithMessage('error', 'Failed to upload image.');
            }
        } else {
            redirectBackWithMessage('error', 'Invalid image type. Allowed: JPG, PNG, GIF.');
        }
    } else {
        redirectBackWithMessage('error', 'No image uploaded.');
    }
}
