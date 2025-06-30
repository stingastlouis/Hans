<?php
include '../../configs/db.php';
include '../../configs/timezoneConfigs.php';
include '../../utils/communicationUtils.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $statusId = trim($_POST['status_id']);
    $eventId = trim($_POST['event_id']);
    $staffId = trim($_POST['staff_id']);
    $date = date('Y-m-d H:i:s');
    if (empty($statusId) || empty($eventId)) {
        redirectBackWithMessage('error', 'Field missing');
    }

    try {
        $date = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("INSERT INTO EventStatus (eventid, statusid, staffid, datecreated) VALUES (:eventid, :statusid, :staffid, :datecreated)");
        $stmt->bindParam(':eventid', $eventId);
        $stmt->bindParam(':statusid', $statusId);
        $stmt->bindParam(':staffid', $staffId);
        $stmt->bindParam(':datecreated', $date);

        if ($stmt->execute()) {
            redirectBackWithMessage('success', 'Event status added successfully.');
        } else {
            redirectBackWithMessage('error', 'Error adding event status.');
        }
    } catch (PDOException $e) {
        redirectBackWithMessage('error', 'Database error: ' . $e->getMessage());
    }
} else {
    redirectBackWithMessage('error', 'Invalid request method.');
}
