<?php
include '../../configs/db.php';
include '../../configs/timezoneConfigs.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $statusId = trim($_POST['status_id']);
    $orderId = trim($_POST['order_id']);
    $staffId = trim($_POST['staff_id']);
    $date = date('Y-m-d H:i:s');
    if (empty($statusId) || empty($orderId)) {
        echo "<h1>Field missing</h1></center>";
        exit;
    }

    try {
        $date = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("INSERT INTO orderstatus (orderid, statusid, staffid, datecreated) VALUES (:orderid, :statusid, :staffid, :datecreated)");
        $stmt->bindParam(':orderid', $orderId);
        $stmt->bindParam(':statusid', $statusId);
        $stmt->bindParam(':staffid', $staffId);
        $stmt->bindParam(':datecreated', $date);

        if ($stmt->execute()) {
            header("Location: ../order.php?success=1");
            exit;
        } else {
            echo "Error adding product.";
        }
    } catch (PDOException $e) {
        echo "Database error: " . $e->getMessage();
    }
} else {
    header("Location: ../order.php");
    exit;
}
?>
