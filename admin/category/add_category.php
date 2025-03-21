<?php
// Include database connection
include '../../configs/db.php';
include '../../configs/timezoneConfigs.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_name = trim($_POST['category_name']);
    
    // Validate input
    if (empty($category_name)) {
        echo "<h1>Category name cannot be empty.</h1></center>";
        exit;
    }

    try {
        // Check if the category name already exists
        $stmt = $conn->prepare("SELECT COUNT(*) FROM categories WHERE name = :name");
        $stmt->bindParam(':name', $category_name);
        $stmt->execute();
        
        // If the category name exists, don't add it and show an error message
        $category_exists = $stmt->fetchColumn();
        if ($category_exists > 0) {
            echo "<div style='background-color: grey; color:red; top: 25vw; position: relative;'><center><h1>Category name already exists. Please choose a different name.</h1></center></div>";
            exit;
        }

        // If category doesn't exist, proceed with insertion
        $date = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("INSERT INTO categories (name, datecreated) VALUES (:name, :datecreated)");
        $stmt->bindParam(':name', $category_name);
        $stmt->bindParam(':datecreated', $date);

        // Execute the query
        if ($stmt->execute()) {
            // Redirect to the categories list page or display success message
            header("Location: ../category.php?success=1");
            exit;
        } else {
            echo "Error adding category.";
        }
    } catch (PDOException $e) {
        // Handle database errors
        echo "Database error: " . $e->getMessage();
    }
} else {
    // Redirect to the categories list page if the request is not POST
    header("Location: ../category.php");
    exit;
}
?>
