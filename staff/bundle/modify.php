<?php
include '../../configs/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bundleId = $_POST['bundle_id'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $discount = $_POST['discount'];

    if (!empty($_FILES['image']['name'])) {
        $targetDir = "../../assets/uploads/bundles/";
        $imageName = basename($_FILES['image']['name']);
        $targetFilePath = $targetDir . $imageName;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFilePath)) {
            $imagePath = $imageName;
        } else {
            header("Location: ../bundle.php?error=upload_failed");
            exit();
        }
    }
    if (isset($imagePath)) {
        $stmt = $conn->prepare("UPDATE Bundle SET Name = :name, Description = :description, ImagePath = :image, Price = :price, DiscountPrice = :discount WHERE Id = :id");
        $stmt->bindParam(':image', $imageName, PDO::PARAM_STR);
    } else {
        $stmt = $conn->prepare("UPDATE Bundle SET Name = :name, Description = :description, Price = :price, DiscountPrice = :discount WHERE Id = :id");
    }

    $stmt->bindParam(':name', $name, PDO::PARAM_STR);
    $stmt->bindParam(':description', $description, PDO::PARAM_STR);
    $stmt->bindParam(':price', $price, PDO::PARAM_STR);
    $stmt->bindParam(':discount', $discount, PDO::PARAM_STR);
    $stmt->bindParam(':id', $bundleId, PDO::PARAM_INT);
    $stmt->execute();

    header("Location: ../bundle.php?success=1");
    exit();
} else {
    header("Location: ../bundle.php?error=1");
    exit();
}
