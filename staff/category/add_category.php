<?php
include '../../configs/db.php';
include '../../configs/timezoneConfigs.php';
include '../../utils/communicationUtils.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_name = trim($_POST['category_name']);

    if (empty($category_name)) {
        redirectBackWithMessage('error', 'Category name cannot be empty.');
    }

    try {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM Categories WHERE name = :name");
        $stmt->bindParam(':name', $category_name);
        $stmt->execute();

        $category_exists = $stmt->fetchColumn();
        if ($category_exists > 0) {
            redirectBackWithMessage('error', 'Category already exists.');
        }

        $date = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("INSERT INTO Categories (name, datecreated) VALUES (:name, :datecreated)");
        $stmt->bindParam(':name', $category_name);
        $stmt->bindParam(':datecreated', $date);

        if ($stmt->execute()) {
            redirectBackWithMessage('success', 'Category successfully added.');
        } else {
            redirectBackWithMessage('error', 'Error adding category.');
        }
    } catch (PDOException $e) {
        redirectBackWithMessage('error', 'Database error: ' . $e->getMessage());
    }
} else {
    redirectBackWithMessage('error', 'Invalid request.');
}
