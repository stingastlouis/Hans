<?php
include '../../configs/db.php';
include '../../configs/timezoneConfigs.php';
include '../../utils/communicationUtils.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $statusId = trim($_POST['status_id']);
    $customerid = trim($_POST['customer_id']);
    $staffId = trim($_POST['staff_id']);

    $date = date('Y-m-d H:i:s');
    if (empty($statusId) || empty($customerid)) {
        redirectBackWithMessage('error', 'Field missing');
    }

    try {
        $date = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("INSERT INTO CustomerStatus (userid, statusid, staffid, datecreated) VALUES (:customerid, :statusid, :staffid, :datecreated)");
        $stmt->bindParam(':customerid', $customerid);
        $stmt->bindParam(':statusid', $statusId);
        $stmt->bindParam(':staffid', $staffId);
        $stmt->bindParam(':datecreated', $date);

        if ($stmt->execute()) {
            redirectBackWithMessage('success', 'Customer status added successfully.');
        } else {
            redirectBackWithMessage('error', 'Error adding customer status.');
        }
    } catch (PDOException $e) {
        redirectBackWithMessage('error', 'Database error: ' . $e->getMessage());
    }
} else {
    redirectBackWithMessage('error', 'Invalid request method.');
}
