<?php 
include '../../configs/db.php';

if (isset($_POST['staff_id'])) {
    $productId = $_POST['staff_id'];

    $stmt = $conn->prepare("DELETE FROM Staff WHERE Id = :id");
    $stmt->bindParam(':id', $productId, PDO::PARAM_INT);
    $stmt->execute();

    header("Location: ../staff.php?success=1");
    exit();
} else {
    header("Location: ../staff.php?error=1");
    exit();
}
