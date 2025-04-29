<?php
include '../../configs/db.php';
include '../../configs/timezoneConfigs.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $staffId = trim($_POST['staff_id']);
    $installationId = trim($_POST['installation_id']);
    $date = date('Y-m-d H:i:s');
    if (empty($staffId) || empty($installationId)) {
        echo "<h1>Field missing</h1></center>";
        exit;
    }

    try {
        $date = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("UPDATE Installation SET staffid= :staffid WHERE id= :installationid");
        $stmt->bindParam(':installationid', $installationId);
        $stmt->bindParam(':staffid', $staffId);

        if ($stmt->execute()) {
            header("Location: ../installation.php");
            exit;
        } else {
            echo "Error adding installation.";
        }
    } catch (PDOException $e) {
        echo "Database error: " . $e->getMessage();
    }
} else {
    header("Location: ../installation.php");
    exit;
}
?>
