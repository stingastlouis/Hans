<?php
include '../../configs/db.php';
include '../../utils/communicationUtils.php';

if (isset($_POST['staff_id'])) {
    $staffId = $_POST['staff_id'];

    $stmt = $conn->prepare("DELETE FROM Staff WHERE Id = :id");
    $stmt->bindParam(':id', $staffId, PDO::PARAM_INT);
    $stmt->execute();

    redirectBackWithMessage('success', 'Staff member deleted successfully.');
} else {
    redirectBackWithMessage('error', 'Failed to delete staff member.');
}
