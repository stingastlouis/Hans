<?php
include '../../configs/db.php';
include '../../configs/timezoneConfigs.php';

$redirectUrl = $_SERVER['HTTP_REFERER'] ?? '../installation.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $statusId = trim($_POST['status_id'] ?? '');
    $installationId = trim($_POST['installation_id'] ?? '');
    $staffId = trim($_POST['staff_id'] ?? '');
    $date = date('Y-m-d H:i:s');

    if (empty($statusId) || empty($installationId)) {
        echo "<h1>Field missing</h1>";
        exit;
    }

    try {
        $conn->beginTransaction();

        $stmt = $conn->prepare("
            INSERT INTO InstallationStatus (installationid, statusid, staffid, datecreated)
            VALUES (:installationid, :statusid, :staffid, :datecreated)
        ");
        $stmt->bindParam(':installationid', $installationId);
        $stmt->bindParam(':statusid', $statusId);
        $stmt->bindParam(':staffid', $staffId);
        $stmt->bindParam(':datecreated', $date);
        $stmt->execute();

        $statusCheckStmt = $conn->prepare("SELECT Name FROM Status WHERE Id = :statusid");
        $statusCheckStmt->execute([':statusid' => $statusId]);
        $statusName = $statusCheckStmt->fetchColumn();

        if (strtolower($statusName) === 'installed') {
            $orderIdStmt = $conn->prepare("SELECT OrderId FROM Installation WHERE Id = :installationid");
            $orderIdStmt->execute([':installationid' => $installationId]);
            $orderId = $orderIdStmt->fetchColumn();

            if ($orderId) {
                $completedStatusStmt = $conn->prepare("SELECT Id FROM Status WHERE Name = 'Completed' LIMIT 1");
                $completedStatusStmt->execute();
                $completedStatusId = $completedStatusStmt->fetchColumn();

                if ($completedStatusId) {
                    $orderStatusStmt = $conn->prepare("
                        INSERT INTO OrderStatus (orderid, statusid, staffid, datecreated)
                        VALUES (:orderid, :statusid, :staffid, :datecreated)
                    ");
                    $orderStatusStmt->execute([
                        ':orderid' => $orderId,
                        ':statusid' => $completedStatusId,
                        ':staffid' => $staffId,
                        ':datecreated' => $date
                    ]);
                }
            }
        }

        $conn->commit();
        header("Location: $redirectUrl?success=1");
        exit;
    } catch (PDOException $e) {
        $conn->rollBack();
        echo "Database error: " . $e->getMessage();
    }
} else {
    header("Location: $redirectUrl");
    exit;
}
