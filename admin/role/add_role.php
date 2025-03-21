<?php
include '../../configs/db.php';
include '../../configs/timezoneConfigs.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role_name = trim($_POST['role_name']);
    
    if (empty($role_name)) {
        echo "<h1>Role name cannot be empty.</h1></center>";
        exit;
    }

    try {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM role WHERE name = :name");
        $stmt->bindParam(':name', $role_name);
        $stmt->execute();
        
        $role_exists = $stmt->fetchColumn();
        if ($role_exists > 0) {
            echo "<div style='background-color: grey; color:red; top: 25vw; position: relative;'><center><h1>Role name already exists. Please choose a different name.</h1></center></div>";
            exit;
        }

        $date = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("INSERT INTO role (name, datecreated) VALUES (:name, :datecreated)");
        $stmt->bindParam(':name', $role_name);
        $stmt->bindParam(':datecreated', $date);

        if ($stmt->execute()) {
            header("Location: ../role.php?success=1");
            exit;
        } else {
            echo "Error adding role.";
        }
    } catch (PDOException $e) {
        echo "Database error: " . $e->getMessage();
    }
} else {
    header("Location: ../role.php");
    exit;
}
?>
