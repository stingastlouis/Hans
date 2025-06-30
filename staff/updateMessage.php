<?php
require_once '../configs/db.php';
include '../sessionManagement.php';

if (!isset($_SESSION['staff_id'])) {
    header("Location: ../unauthorised.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['query_id'])) {
    $queryId = intval($_POST['query_id']);

    try {
        $stmt = $conn->prepare("UPDATE Query SET Seen = TRUE WHERE Id = ?");
        $stmt->execute([$queryId]);
    } catch (PDOException $e) {
        die("Failed to update: " . $e->getMessage());
    }
}

// Redirect back to query listing
header("Location: admin-messages.php");
exit;
