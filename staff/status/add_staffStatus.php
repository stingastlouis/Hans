<?php
include '../../configs/db.php';
include '../../configs/timezoneConfigs.php';
include '../../utils/communicationUtils.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $statusId = trim($_POST['status_id']);
    $staffId = trim($_POST['staff_id']);
    $modify_by = trim($_POST['modify_by']);
    $date = date('Y-m-d H:i:s');
    if (empty($statusId) || empty($staffId)) {
        redirectBackWithMessage('error', 'Fields missing');
    }

    try {
        $date = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("INSERT INTO StaffStatus (staffid, statusid, modifyby, datecreated) VALUES (:staffid, :statusid, :modifyby, :datecreated)");
        $stmt->bindParam(':staffid', $staffId);
        $stmt->bindParam(':statusid', $statusId);
        $stmt->bindParam(':modifyby', $modify_by);
        $stmt->bindParam(':datecreated', $date);

        if ($stmt->execute()) {
            redirectBackWithMessage('success', 'Staff status added successfully.');
        } else {
            redirectBackWithMessage('error', 'Error adding staff status.');
        }
    } catch (PDOException $e) {
        redirectBackWithMessage('error', 'Database error: ' . $e->getMessage());
    }
} else {
    redirectBackWithMessage('error', 'Invalid request method.');
}
