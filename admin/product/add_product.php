<?php
include '../../configs/db.php';
include '../../configs/timezoneConfigs.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $name = $_POST['product_name'];
    $categoryId = $_POST['product_category_id'];
    $description = $_POST['product_description'];
    $price = $_POST['product_price'];
    $discount_price = $_POST['product_discount'];
    $stock = $_POST['product_stock'];
    

    // File upload handling
    if (!empty($_FILES['product_image']['name'])) {
        $upload_dir = '../../assets/uploads/';
        $file_name = basename($_FILES['product_image']['name']);
        $target_file = $upload_dir . $file_name;

        // Validate file type
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = mime_content_type($_FILES['product_image']['tmp_name']);

        if (in_array($file_type, $allowed_types)) {
            // Move the file to the uploads directory
            if (move_uploaded_file($_FILES['product_image']['tmp_name'], $target_file)) {
                try {
                    // Start transaction
                    $conn->beginTransaction();

                    // Insert product into the database
                    $stmt = $conn->prepare("INSERT INTO Products (Name, CategoryId, Description, Price, DiscountPrice, Stock, ImagePath, DateCreated) 
                                            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                    $stmt->execute([$name, $categoryId, $description, $price, $discount_price, $stock, $file_name]);

                    // Check if the insertion was successful
                    if ($stmt->rowCount() > 0) {
                        // Get the ID of the newly created product
                        $productId = $conn->lastInsertId();

                        // Fetch the ID of the "ACTIVE" status
                        $statusStmt = $conn->prepare("SELECT Id FROM Status WHERE Name = 'ACTIVE' LIMIT 1");
                        $statusStmt->execute();
                        $statusRow = $statusStmt->fetch(PDO::FETCH_ASSOC);

                        if ($statusRow) {
                            $statusId = $statusRow['Id'];

                            // Insert into productstatus table
                            $statusInsertStmt = $conn->prepare("INSERT INTO productstatus (productid, statusid, datecreated) 
                                                                VALUES (?, ?, NOW())");
                            $statusInsertStmt->execute([$productId, $statusId]);

                            // Commit transaction
                            $conn->commit();

                            // Redirect to the product list page with success message
                            header('Location: ../product.php?success=1');
                            exit;
                        } else {
                            throw new Exception("Error: 'ACTIVE' status not found.");
                        }
                    } else {
                        throw new Exception("Error: Unable to insert the product into the database.");
                    }
                } catch (Exception $e) {
                    // Rollback transaction on error
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
