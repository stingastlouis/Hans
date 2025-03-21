<?php
include '../../configs/db.php';

if (isset($_POST['role_id'])) {
    $categoryId = $_POST['role_id'];
    $stmt = $conn->prepare("DELETE FROM role WHERE Id = :id");
    $stmt->bindParam(':id', $categoryId, PDO::PARAM_INT);
    $stmt->execute();

    header("Location: ../role.php?success=1");
    exit();
} else {
    header("Location: ../role.php?error=1");
    exit();
}
