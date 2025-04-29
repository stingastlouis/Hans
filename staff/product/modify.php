<?php
include '../../configs/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = $_POST['product_id'];
    $name = $_POST['product_name'];
    $description = $_POST['product_description'];
    $price = $_POST['product_price'];
    $discount = $_POST['product_discount'];
    $stock = $_POST['product_stock'];
    $category_id = $_POST['product_category_id'];

    $imagePath = null;

    if (!empty($_FILES['product_image']['name'])) {
        $targetDir = "../../assets/uploads/";
        $imageName = basename($_FILES['product_image']['name']);
        $targetFilePath = $targetDir . $imageName;

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        if (move_uploaded_file($_FILES['product_image']['tmp_name'], $targetFilePath)) {
            $imagePath = $imageName;
        } else {
            header("Location: ../product.php?error=1");
            exit();
        }
    }

    if ($imagePath !== null) {
        $stmt = $conn->prepare("UPDATE Products SET Name = :name, Description = :description, ImagePath = :image, Price = :price, DiscountPrice = :discount, Stock = :stock, CategoryId = :categoryId WHERE Id = :id");
        $stmt->bindParam(':image', $imagePath, PDO::PARAM_STR);
    } else {
        $stmt = $conn->prepare("UPDATE Products SET Name = :name, Description = :description, Price = :price, DiscountPrice = :discount, Stock = :stock, CategoryId = :categoryId WHERE Id = :id");
    }

    $stmt->bindParam(':name', $name, PDO::PARAM_STR);
    $stmt->bindParam(':description', $description, PDO::PARAM_STR);
    $stmt->bindParam(':price', $price, PDO::PARAM_STR);
    $stmt->bindParam(':discount', $discount, PDO::PARAM_STR);
    $stmt->bindParam(':stock', $stock, PDO::PARAM_INT);
    $stmt->bindParam(':categoryId', $category_id, PDO::PARAM_INT);
    $stmt->bindParam(':id', $productId, PDO::PARAM_INT);
    
    $stmt->execute();

    header("Location: ../product.php?success=1");
    exit();
} else {
    header("Location: ../product.php?error=1");
    exit();
}
