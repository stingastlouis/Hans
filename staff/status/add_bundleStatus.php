<?php
include '../../configs/db.php';
include '../../configs/timezoneConfigs.php';
include '../../utils/communicationUtils.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $statusId = trim($_POST['status_id']);
    $bundleId = trim($_POST['bundle_id']);
    $staffId = trim($_POST['staff_id']);
    $date = date('Y-m-d H:i:s');
    if (empty($statusId) || empty($bundleId)) {
        redirectBackWithMessage('error', 'Field missing');
    }

    try {
        $date = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("INSERT INTO BundleStatus (bundleid, statusid, staffid, datecreated) VALUES (:bundleid, :statusid, :staffid, :datecreated)");
        $stmt->bindParam(':bundleid', $bundleId);
        $stmt->bindParam(':statusid', $statusId);
        $stmt->bindParam(':staffid', $staffId);
        $stmt->bindParam(':datecreated', $date);

        if ($stmt->execute()) {
            redirectBackWithMessage('success', 'Bundle status added successfully.');
        } else {
            redirectBackWithMessage('error', 'Error adding bundle.');
        }
    } catch (PDOException $e) {
        redirectBackWithMessage('error', 'Database error: ' . $e->getMessage());
    }
} else {
    redirectBackWithMessage('error', 'Invalid request method.');
}
