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
        $stmt = $conn->prepare("SELECT COUNT(*) FROM Role WHERE name = :name");
        $stmt->bindParam(':name', $role_name);
        $stmt->execute();

        $role_exists = $stmt->fetchColumn();
        if ($role_exists > 0) {
            header("Location: ../role.php?error=1");
            exit;
        }

        $date = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("INSERT INTO Role (name, datecreated) VALUES (:name, :datecreated)");
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
