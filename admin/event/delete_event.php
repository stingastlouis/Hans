<?php 
include '../../configs/db.php';

if (isset($_POST['event_id'])) {
    $eventId = $_POST['event_id'];
    $stmt = $conn->prepare("DELETE FROM Event WHERE ID = :id");
    $stmt->bindParam(':id', $eventId, PDO::PARAM_INT);
    $stmt->execute();

    header("Location: ../event.php?success=1");
    exit();
} else {
    header("Location: ../event.php?error=1");
    exit();
}
