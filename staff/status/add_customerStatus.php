<?php
include '../../configs/db.php';
include '../../configs/timezoneConfigs.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $statusId = trim($_POST['status_id']);
    $customerid = trim($_POST['customer_id']);
    var_dump($statusId,"status");
    var_dump($customerid,"customerid");
    $date = date('Y-m-d H:i:s');
    if (empty($statusId) || empty($customerid)) {
        echo "<h1>Field missing</h1></center>";
        exit;
    }

    try {
        $date = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("INSERT INTO customerstatus (userid, statusid, datecreated) VALUES (:customerid, :statusid, :datecreated)");
        $stmt->bindParam(':customerid', $customerid);
        $stmt->bindParam(':statusid', $statusId);
        $stmt->bindParam(':datecreated', $date);

        if ($stmt->execute()) {
            header("Location: ../customer.php?success=1");
            exit;
        } else {
            echo "Error adding customer.";
        }
    } catch (PDOException $e) {
        echo "Database error: " . $e->getMessage();
    }
} else {
    header("Location: ../customer.php");
    exit;
}
?>
