<?php
include '../../configs/db.php';
include '../../configs/timezoneConfigs.php';
include '../../utils/communicationUtils.php';



if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $staffId = trim($_POST['staff_id']);
    $installationId = trim($_POST['installation_id']);
    $date = date('Y-m-d H:i:s');
    if (empty($staffId) || empty($installationId)) {
        redirectBackWithMessage('error', 'Field missing');
    }

    try {
        $date = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("UPDATE Installation SET staffid= :staffid WHERE id= :installationid");
        $stmt->bindParam(':installationid', $installationId);
        $stmt->bindParam(':staffid', $staffId);

        if ($stmt->execute()) {
            redirectBackWithMessage('success', 'Installation updated successfully.');
        } else {
            redirectBackWithMessage('error', 'Error adding installation.');
        }
    } catch (PDOException $e) {
        redirectBackWithMessage('error', 'Failed to update installation. ' . $e->getMessage());
        exit;
    }
} else {
    redirectBackWithMessage('error', 'Invalid request method.');
}
