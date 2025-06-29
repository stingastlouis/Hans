<?php
include '../../configs/db.php';
include '../../configs/timezoneConfigs.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['bundle_name'];
    $description = $_POST['bundle_description'];
    $staffId = $_POST["staff_id"];
    $price = $_POST['bundle_price'];
    $discount_price = $_POST['bundle_discount_price'];

    $dateNow = date('Y-m-d H:i:s');

    if (!empty($_FILES['bundle_image']['name'])) {
        $upload_dir = '../../assets/uploads/bundles/';
        $file_name = basename($_FILES['bundle_image']['name']);
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
                            header('Location: ../bundle.php?success=1');
                            exit;
                        } else {
                            header('Location: ../bundle.php?error=1');
                            exit;
                        }
                    } else {
                        header('Location: ../bundle.php?error=1');
                        exit;
                    }
                } catch (Exception $e) {
                    $conn->rollBack();
                    header('Location: ../bundle.php?error=1');
                    exit;
                }
            } else {
                header('Location: ../bundle.php?error=1');
                exit;
            }
        } else {
            header('Location: ../bundle.php?error=1');
            exit;
        }
    } else {
        header('Location: ../bundle.php?error=1');
        exit;
    }
}
