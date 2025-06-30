<?php
include '../../configs/db.php';
include '../../configs/timezoneConfigs.php';
include '../../utils/communicationUtils.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['product_name'];
    $categoryId = $_POST['product_category_id'];
    $description = $_POST['product_description'];
    $price = $_POST['product_price'];
    $staffId = $_POST["staff_id"];
    $discount_price = $_POST['product_discount'];
    $stock = $_POST['product_stock'];

    if (!empty($_FILES['product_image']['name'])) {
        $upload_dir = '../../assets/uploads/products/';
        $file_name = basename($_FILES['product_image']['name']);
        $target_file = $upload_dir . $file_name;
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = mime_content_type($_FILES['product_image']['tmp_name']);

        if (in_array($file_type, $allowed_types)) {

            if (move_uploaded_file($_FILES['product_image']['tmp_name'], $target_file)) {
                try {
                    $conn->beginTransaction();

                    $stmt = $conn->prepare("INSERT INTO Products (Name, CategoryId, Description, Price, DiscountPrice, Stock, ImagePath, DateCreated) 
                                            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                    $stmt->execute([$name, $categoryId, $description, $price, $discount_price, $stock, $file_name]);

                    if ($stmt->rowCount() > 0) {
                        $productId = $conn->lastInsertId();
                        $statusStmt = $conn->prepare("SELECT Id FROM Status WHERE Name = 'ACTIVE' LIMIT 1");
                        $statusStmt->execute();
                        $statusRow = $statusStmt->fetch(PDO::FETCH_ASSOC);

                        if ($statusRow) {
                            $statusId = $statusRow['Id'];
                            $statusInsertStmt = $conn->prepare("INSERT INTO ProductStatus (productid, statusid, staffid, datecreated) 
                                                                VALUES (?, ?, ?, NOW())");
                            $statusInsertStmt->execute([$productId, $statusId, $staffId]);

                            $conn->commit();

                            redirectBackWithMessage('success', 'Product successfully added.');
                        } else {
                            redirectBackWithMessage('error', 'Failed to set product status.');
                        }
                    } else {
                        redirectBackWithMessage('error', 'Failed to add product.');
                    }
                } catch (Exception $e) {
                    $conn->rollBack();
                    redirectBackWithMessage('error', 'An error occurred while adding the product.');
                }
            } else {
                redirectBackWithMessage('error', 'Failed to upload image.');
            }
        } else {
            redirectBackWithMessage('error', 'Invalid file type.');
        }
    } else {
        redirectBackWithMessage('error', 'No image uploaded.');
    }
}
