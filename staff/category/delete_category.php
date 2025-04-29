<?php
include '../../configs/db.php';

if (isset($_POST['category_id'])) {
    $categoryId = $_POST['category_id'];
    $stmt = $conn->prepare("DELETE FROM Categories WHERE Id = :id");
    $stmt->bindParam(':id', $categoryId, PDO::PARAM_INT);
    $stmt->execute();

    header("Location: ../category.php?success=1");
    exit();
} else {
    header("Location: ../category.php?error=1");
    exit();
}
