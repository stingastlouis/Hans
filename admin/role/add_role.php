<?php
// Include database connection
include '../../configs/db.php';
include '../../configs/timezoneConfigs.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role_name = trim($_POST['role_name']);
    
    // Validate input
    if (empty($role_name)) {
        echo "<h1>Role name cannot be empty.</h1></center>";
        exit;
    }

    try {
        // Check if the role name already exists
        $stmt = $conn->prepare("SELECT COUNT(*) FROM role WHERE name = :name");
        $stmt->bindParam(':name', $role_name);
        $stmt->execute();
        
        // If the role name exists, don't add it and show an error message
        $role_exists = $stmt->fetchColumn();
        if ($role_exists > 0) {
            echo "<div style='background-color: grey; color:red; top: 25vw; position: relative;'><center><h1>Role name already exists. Please choose a different name.</h1></center></div>";
            exit;
        }

        // If role doesn't exist, proceed with insertion
        $date = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("INSERT INTO role (name, datecreated) VALUES (:name, :datecreated)");
        $stmt->bindParam(':name', $role_name);
        $stmt->bindParam(':datecreated', $date);

        // Execute the query
        if ($stmt->execute()) {
            // Redirect to the Role list page or display success message
            header("Location: ../role.php?success=1");
            exit;
        } else {
            echo "Error adding role.";
        }
    } catch (PDOException $e) {
        // Handle database errors
        echo "Database error: " . $e->getMessage();
    }
} else {
    // Redirect to the Role list page if the request is not POST
    header("Location: ../role.php");
    exit;
}
?>
