<?php 
include '../../configs/db.php';

if (isset($_POST['product_id'])) {
    $productId = $_POST['product_id'];

    $stmt = $conn->prepare("DELETE FROM Products WHERE ID = :id");
    $stmt->bindParam(':id', $productId, PDO::PARAM_INT);
    $stmt->execute();

    header("Location: ../product.php?success=1");
    exit();
} else {
    header("Location: ../product.php?error=1");
    exit();
}
