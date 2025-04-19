<?php
include '../../configs/db.php';
include '../../configs/timezoneConfigs.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_name = trim($_POST['category_name']);
    
    if (empty($category_name)) {
        echo "<h1>Category name cannot be empty.</h1></center>";
        exit;
    }

    try {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM categories WHERE name = :name");
        $stmt->bindParam(':name', $category_name);
        $stmt->execute();
        
        $category_exists = $stmt->fetchColumn();
        if ($category_exists > 0) {
            echo "<div style='background-color: grey; color:red; top: 25vw; position: relative;'><center><h1>Category name already exists. Please choose a different name.</h1></center></div>";
            exit;
        }

        $date = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("INSERT INTO categories (name, datecreated) VALUES (:name, :datecreated)");
        $stmt->bindParam(':name', $category_name);
        $stmt->bindParam(':datecreated', $date);

        if ($stmt->execute()) {
            header("Location: ../category.php?success=1");
            exit;
        } else {
            echo "Error adding category.";
        }
    } catch (PDOException $e) {
        echo "Database error: " . $e->getMessage();
    }
} else {
    header("Location: ../category.php");
    exit;
}
?>
