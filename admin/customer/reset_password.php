<?php
include '../../configs/db.php';
include '../../configs/timezoneConfigs.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $customerId = $_POST['customer_id'];
    $newPassword = $_POST['customer_password'];

    try {
        // Start transaction
        $conn->beginTransaction();

        // Validate customer ID
        if (empty($customerId)) {
            throw new Exception("Customer ID is required");
        }

        // Validate password strength (adjust regex as needed)
        if (!preg_match("/^(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[\W_]).{8,}$/", $newPassword)) {
            throw new Exception("Password must be at least 8 characters long and include a mix of uppercase, lowercase, numbers, and special characters");
        }

        // Check if customer exists
        $checkCustomer = $conn->prepare("SELECT COUNT(*) FROM Customer WHERE Id = ?");
        $checkCustomer->execute([$customerId]);
        if ($checkCustomer->fetchColumn() == 0) {
            throw new Exception("Customer member not found");
        }

        // Hash the new password securely
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

        // Update the password in the database
        $stmt = $conn->prepare("UPDATE Customer SET PasswordHash = ? WHERE Id = ?");
        $stmt->execute([$passwordHash, $customerId]);

        // Check if the update was successful
        if ($stmt->rowCount() > 0) {
            // Commit transaction
            $conn->commit();

            // Redirect to the customer list page with success message
            header('Location: ../customer.php?success=1');
            exit;
        } else {
            throw new Exception("Error: Unable to reset the password. No changes were made.");
        }

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollBack();

        // Handle specific error messages
        $errorMessage = $e->getMessage();
        if (strpos($errorMessage, "Password must be") !== false) {
            header('Location: ../customer.php?error=weak_password');
        } else if (strpos($errorMessage, "Customer member not found") !== false) {
            header('Location: ../customer.php?error=customer_not_found');
        } else {
            // Generic error
            header('Location: ../customer.php?error=general&message=' . urlencode($errorMessage));
        }
        exit;
    }
} else {
    // If not POST request, redirect to customer page
    header('Location: ../customer.php');
    exit;
}
?>
