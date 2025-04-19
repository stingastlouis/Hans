<?php
include '../../configs/db.php';
include '../../configs/timezoneConfigs.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $statusId = trim($_POST['status_id']);
    $productId = trim($_POST['product_id']);
    $date = date('Y-m-d H:i:s');
    if (empty($statusId) || empty( $productId)) {
        echo "<h1>Field missing</h1></center>";
        exit;
    }

    try {
        $date = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("INSERT INTO productstatus (productid, statusid, datecreated) VALUES (:productid, :statusid, :datecreated)");
        $stmt->bindParam(':productid', $productId);
        $stmt->bindParam(':statusid', $statusId);
        $stmt->bindParam(':datecreated', $date);

        if ($stmt->execute()) {
            header("Location: ../product.php?success=1");
            exit;
        } else {
            echo "Error adding product.";
        }
    } catch (PDOException $e) {
        echo "Database error: " . $e->getMessage();
    }
} else {
    header("Location: ../product.php");
    exit;
}
?>
