<?php
include '../../configs/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $eventId = $_POST['event_id'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $discount = $_POST['discount'];

    // Handle image upload
    if (!empty($_FILES['image']['name'])) {
        $targetDir = "../../assets/uploads/";
        $imageName = basename($_FILES['image']['name']);
        $targetFilePath = $targetDir . $imageName;

        // Validate and move uploaded file
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFilePath)) {
            $imagePath = $imageName;
        } else {
            header("Location: ../event.php?error=upload_failed");
            exit();
        }
    }

    // Update the event in the database
    if (isset($imagePath)) {
        $stmt = $conn->prepare("UPDATE Event SET Name = :name, Description = :description, ImagePath = :image, Price = :price, DiscountPrice = :discount WHERE Id = :id");
        $stmt->bindParam(':image', $imageName, PDO::PARAM_STR);
    } else {
        $stmt = $conn->prepare("UPDATE Event SET Name = :name, Description = :description, Price = :price, DiscountPrice = :discount WHERE Id = :id");
    }

    $stmt->bindParam(':name', $name, PDO::PARAM_STR);
    $stmt->bindParam(':description', $description, PDO::PARAM_STR);
    $stmt->bindParam(':price', $price, PDO::PARAM_STR);
    $stmt->bindParam(':discount', $discount, PDO::PARAM_STR);
    $stmt->bindParam(':id', $eventId, PDO::PARAM_INT);
    $stmt->execute();

    header("Location: ../event.php?success=1");
    exit();
} else {
    header("Location: ../event.php?error=invalid_request");
    exit();
}
