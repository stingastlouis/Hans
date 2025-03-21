<?php 
include '../../configs/db.php';

if (isset($_POST['event_id'])) {
    $eventId = $_POST['event_id'];

    // Prepare and execute the delete query
    $stmt = $conn->prepare("DELETE FROM Event WHERE ID = :id");
    $stmt->bindParam(':id', $eventId, PDO::PARAM_INT);
    $stmt->execute();

    // Redirect with success
    header("Location: ../event.php?success=1");
    exit();
} else {
    // Redirect back with an error if no event ID was provided
    header("Location: ../event.php?error=1");
    exit();
}
