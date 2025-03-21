<?php
include '../../configs/db.php';
include '../../configs/timezoneConfigs.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $statusId = trim($_POST['status_id']);
    $staffId = trim($_POST['staff_id']);
    $date = date('Y-m-d H:i:s');
    if (empty($statusId) || empty( $staffId)) {
        echo "<h1>Field missing</h1></center>";
        exit;
    }

    try {
        $date = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("INSERT INTO staffstatus (staffid, statusid, datecreated) VALUES (:staffid, :statusid, :datecreated)");
        $stmt->bindParam(':staffid', $staffId);
        $stmt->bindParam(':statusid', $statusId);
        $stmt->bindParam(':datecreated', $date);

        if ($stmt->execute()) {
            header("Location: ../staff.php?success=1");
            exit;
        } else {
            echo "Error adding staff.";
        }
    } catch (PDOException $e) {
        echo "Database error: " . $e->getMessage();
    }
} else {
    header("Location: ../staff.php");
    exit;
}
?>
