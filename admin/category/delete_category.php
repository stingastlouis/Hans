<?php
include '../../configs/db.php';

if (isset($_POST['category_id'])) {
    $categoryId = $_POST['category_id'];

    var_dump($categoryId);
    // Prepare and execute the delete query
    $stmt = $conn->prepare("DELETE FROM categories WHERE Id = :id");
    $stmt->bindParam(':id', $categoryId, PDO::PARAM_INT);
    $stmt->execute();

    header("Location: ../category.php?success=1");
    exit();
} else {
    // Redirect back with an error if no category ID was provided
    header("Location: ../category.php?error=1");
    exit();
}
