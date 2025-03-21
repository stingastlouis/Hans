<?php
include '../../configs/db.php';

if (isset($_POST['role_id'])) {
    $categoryId = $_POST['role_id'];

    var_dump($categoryId);
    // Prepare and execute the delete query
    $stmt = $conn->prepare("DELETE FROM role WHERE Id = :id");
    $stmt->bindParam(':id', $categoryId, PDO::PARAM_INT);
    $stmt->execute();

    header("Location: ../role.php?success=1");
    exit();
} else {
    // Redirect back with an error if no category ID was provided
    header("Location: ../role.php?error=1");
    exit();
}
