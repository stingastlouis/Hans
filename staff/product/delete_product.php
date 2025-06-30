<?php
include '../../configs/db.php';
include '../../utils/communicationUtils.php';

if (isset($_POST['product_id'])) {
    $productId = $_POST['product_id'];

    $stmt = $conn->prepare("DELETE FROM Products WHERE ID = :id");
    $stmt->bindParam(':id', $productId, PDO::PARAM_INT);
    $stmt->execute();

    redirectBackWithMessage('success', 'Product successfully deleted.');
} else {
    redirectBackWithMessage('error', 'Invalid request.');
}
