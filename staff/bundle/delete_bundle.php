<?php
include '../../configs/db.php';
include '../../utils/communicationUtils.php';
if (isset($_POST['bundle_id']) && is_numeric($_POST['bundle_id'])) {
    $bundleId = (int)$_POST['bundle_id'];

    try {
        $stmt = $conn->prepare("DELETE FROM Bundle WHERE ID = :id");
        $stmt->bindParam(':id', $bundleId, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            redirectBackWithMessage('success', 'Bundle successfully deleted.');
        } else {
            redirectBackWithMessage('error', 'No record found.');
        }
    } catch (PDOException $e) {
        redirectBackWithMessage('error', 'An error occurred while deleting the bundle.');
    }
    exit();
} else {
    redirectBackWithMessage('error', 'Invalid request.');
}
