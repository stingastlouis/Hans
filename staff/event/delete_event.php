<?php
include '../../configs/db.php';
include '../../utils/communicationUtils.php';

if (isset($_POST['event_id'])) {
    $eventId = $_POST['event_id'];
    $stmt = $conn->prepare("DELETE FROM Event WHERE ID = :id");
    $stmt->bindParam(':id', $eventId, PDO::PARAM_INT);
    $stmt->execute();

    redirectBackWithMessage('success', 'Event successfully deleted.');
} else {
    redirectBackWithMessage('error', 'Invalid request.');
}
