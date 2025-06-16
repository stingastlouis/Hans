<?php
include '../../configs/db.php';

if (isset($_POST['bundle_id']) && is_numeric($_POST['bundle_id'])) {
    $bundleId = (int)$_POST['bundle_id'];

    try {
        $stmt = $conn->prepare("DELETE FROM Bundle WHERE ID = :id");
        $stmt->bindParam(':id', $bundleId, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            header("Location: ../bundle.php?success=1");
        } else {
            header("Location: ../bundle.php?error=norecord");
        }
    } catch (PDOException $e) {
        header("Location: ../bundle.php?error=exception");
    }
    exit();
} else {
    header("Location: ../bundle.php?error=1");
    exit();
}
