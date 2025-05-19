<?php 
include '../../configs/db.php';

if (isset($_POST['bundle_id'])) {
    $bundleId = $_POST['bundle_id'];
    $stmt = $conn->prepare("DELETE FROM Bundle WHERE ID = :id");
    $stmt->bindParam(':id', $bundleId, PDO::PARAM_INT);
    $stmt->execute();

    header("Location: ../bundle.php?success=1");
    exit();
} else {
    header("Location: ../bundle.php?error=1");
    exit();
}
