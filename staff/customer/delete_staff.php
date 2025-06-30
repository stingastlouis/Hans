<?php
include '../../configs/db.php';
include '../../utils/communicationUtils.php';
if (isset($_POST['customer_id'])) {
    $productId = $_POST['customer_id'];

    $stmt = $conn->prepare("DELETE FROM Customer WHERE Id = :id");
    $stmt->bindParam(':id', $productId, PDO::PARAM_INT);
    $stmt->execute();

    redirectBackWithMessage('success', 'Customer successfully deleted.');
} else {
    redirectBackWithMessage('error', 'Invalid request.');
}
