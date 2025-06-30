<?php
include '../../configs/db.php';
include '../../configs/timezoneConfigs.php';
include '../../utils/communicationUtils.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $statusId = trim($_POST['status_id']);
    $productId = trim($_POST['product_id']);
    $staffId = trim($_POST['staff_id']);
    $date = date('Y-m-d H:i:s');
    if (empty($statusId) || empty($productId)) {
        redirectBackWithMessage('error', 'Field missing');
    }

    try {
        $date = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("INSERT INTO ProductStatus (productid, statusid, staffid, datecreated) VALUES (:productid, :statusid, :staffid, :datecreated)");
        $stmt->bindParam(':productid', $productId);
        $stmt->bindParam(':statusid', $statusId);
        $stmt->bindParam(':staffid', $staffId);
        $stmt->bindParam(':datecreated', $date);

        if ($stmt->execute()) {
            redirectBackWithMessage('success', 'Product status added successfully.');
        } else {
            redirectBackWithMessage('error', 'Product status modification error');
        }
    } catch (PDOException $e) {
        redirectBackWithMessage('error', 'Database error: ' . $e->getMessage());
    }
} else {
    redirectBackWithMessage('error', 'Invalid request method.');
}
