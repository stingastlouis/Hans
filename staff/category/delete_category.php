<?php
include '../../configs/db.php';
include '../../utils/communicationUtils.php';
if (isset($_POST['category_id'])) {
    $categoryId = $_POST['category_id'];
    $stmt = $conn->prepare("DELETE FROM Categories WHERE Id = :id");
    $stmt->bindParam(':id', $categoryId, PDO::PARAM_INT);
    $stmt->execute();

    redirectBackWithMessage('success', 'Category successfully deleted.');
} else {
    redirectBackWithMessage('error', 'Invalid request.');
}
