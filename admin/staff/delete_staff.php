<?php 
include '../../configs/db.php';

if (isset($_POST['customer_id'])) {
    $productId = $_POST['customer_id'];

    $stmt = $conn->prepare("DELETE FROM Customer WHERE Id = :id");
    $stmt->bindParam(':id', $productId, PDO::PARAM_INT);
    $stmt->execute();

    header("Location: ../customer.php?success=1");
    exit();
} else {
    header("Location: ../customer.php?error=1");
    exit();
}
