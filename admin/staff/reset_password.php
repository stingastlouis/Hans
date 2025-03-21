<?php
include '../../configs/db.php';
include '../../configs/timezoneConfigs.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $staffId = $_POST['staff_id'];
    $newPassword = $_POST['staff_password'];

    try {
        // Start transaction
        $conn->beginTransaction();

        // Validate staff ID
        if (empty($staffId)) {
            throw new Exception("Staff ID is required");
        }

        // Validate password strength (adjust regex as needed)
        if (!preg_match("/^(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[\W_]).{8,}$/", $newPassword)) {
            throw new Exception("Password must be at least 8 characters long and include a mix of uppercase, lowercase, numbers, and special characters");
        }

        // Check if staff exists
        $checkStaff = $conn->prepare("SELECT COUNT(*) FROM Staff WHERE Id = ?");
        $checkStaff->execute([$staffId]);
        if ($checkStaff->fetchColumn() == 0) {
            throw new Exception("Staff member not found");
        }

        // Hash the new password securely
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

        // Update the password in the database
        $stmt = $conn->prepare("UPDATE Staff SET PasswordHash = ? WHERE Id = ?");
        $stmt->execute([$passwordHash, $staffId]);

        // Check if the update was successful
        if ($stmt->rowCount() > 0) {
            // Commit transaction
            $conn->commit();

            // Redirect to the staff list page with success message
            header('Location: ../staff.php?success=1');
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
            header('Location: ../staff.php?error=weak_password');
        } else if (strpos($errorMessage, "Staff member not found") !== false) {
            header('Location: ../staff.php?error=staff_not_found');
        } else {
            // Generic error
            header('Location: ../staff.php?error=general&message=' . urlencode($errorMessage));
        }
        exit;
    }
} else {
    // If not POST request, redirect to staff page
    header('Location: ../staff.php');
    exit;
}
?>
