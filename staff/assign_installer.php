<?php
include '../configs/db.php';
include '../configs/timezoneConfigs.php';
include '../../utils/communicationUtils.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = trim($_POST['orderId']);
    $staffId = trim($_POST['staffId']);
    $installerId = trim($_POST['installerId']);
    $date = date('Y-m-d H:i:s');

    if (empty($orderId) || empty($staffId) || empty($installerId)) {
        redirectBackWithMessage('error', 'Missing required fields.');
    }

    try {
        $conn->beginTransaction();

        $stmt = $conn->prepare("UPDATE Installation SET StaffId = :installerId WHERE OrderId = :orderId");
        $stmt->bindParam(':installerId', $installerId);
        $stmt->bindParam(':orderId', $orderId);
        $stmt->execute();

        $installationIdstmt = $conn->prepare("SELECT Id FROM Installation WHERE OrderId = :orderId LIMIT 1");
        $installationIdstmt->bindParam(':orderId', $orderId);
        $installationIdstmt->execute();
        $installationRow = $installationIdstmt->fetch(PDO::FETCH_ASSOC);

        if (!$installationRow) {
            $conn->rollBack();
            redirectBackWithMessage("error", "Delivery not found for this order.");
        }

        $installationId = $installationRow['Id'];
        $statusStmt = $conn->prepare("SELECT Id FROM Status WHERE Name = 'PROCESSING' LIMIT 1");
        $statusStmt->execute();
        $status = $statusStmt->fetch(PDO::FETCH_ASSOC);

        if (!$status) {
            $conn->rollBack();
            redirectBackWithMessage("error", "Pending status not found in database");
        }

        $pendingStatusId = $status['Id'];
        $insertStmt = $conn->prepare("
            INSERT INTO InstallationStatus (InstallationId, StatusId, StaffId, DateCreated)
            VALUES (:installationId, :statusId, :staffId, :dateCreated)
        ");

        $insertStmt->bindParam(':installationId', $installationId);
        $insertStmt->bindParam(':statusId', $pendingStatusId);
        $insertStmt->bindParam(':staffId', $staffId);
        $insertStmt->bindParam(':dateCreated', $date);
        $insertStmt->execute();
        $conn->commit();
        redirectBackWithMessage("success", "Employee assigned and status set to PENDING successfully!");
    } catch (PDOException $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        redirectBackWithMessage("error", "Database Error: " . $e->getMessage());
    }
} else {
    redirectBackWithMessage("error", "Invalid request method.");
}
