<?php
include '../../configs/db.php';
include '../../configs/timezoneConfigs.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $staffId = $_POST['staff_id'];
    $fullname = $_POST['staff_fullname'];
    $email = $_POST['staff_email'];
    $phone = $_POST['staff_phone'];
    $roleId = $_POST['staff_role_id'];

    try {
        // Start transaction
        $conn->beginTransaction();

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }

        // Check if email already exists for other staff members
        $checkEmail = $conn->prepare("SELECT COUNT(*) FROM Staff WHERE Email = ? AND Id != ?");
        $checkEmail->execute([$email, $staffId]);
        if ($checkEmail->fetchColumn() > 0) {
            throw new Exception("Email already exists");
        }

        // Validate phone number (basic validation - adjust as needed)
        if (!preg_match("/^[0-9+\-\s()]*$/", $phone)) {
            throw new Exception("Invalid phone number format");
        }

        // Check if staff exists
        $checkStaff = $conn->prepare("SELECT COUNT(*) FROM Staff WHERE Id = ?");
        $checkStaff->execute([$staffId]);
        if ($checkStaff->fetchColumn() == 0) {
            throw new Exception("Staff member not found");
        }

        // Update staff member in the database
        $stmt = $conn->prepare("UPDATE Staff 
                               SET Fullname = ?, 
                                   Email = ?, 
                                   Phone = ?, 
                                   RoleId = ?
                               WHERE Id = ?");
        
        $stmt->execute([
            $fullname,
            $email,
            $phone,
            $roleId,
            $staffId
        ]);

        // Check if the update was successful
        if ($stmt->rowCount() >= 0) { // Using >= 0 because UPDATE might not change any values
            // Commit transaction
            $conn->commit();

            // Redirect to the staff list page with success message
            header('Location: ../staff.php?success=2'); // Using 2 to differentiate from add success
            exit;
        } else {
            throw new Exception("Error: Unable to update the staff member in the database.");
        }

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollBack();
        
        // Handle specific error messages
        $errorMessage = $e->getMessage();
        if (strpos($errorMessage, "Invalid email") !== false) {
            header('Location: ../staff.php?error=invalid_email');
        } else if (strpos($errorMessage, "Email already exists") !== false) {
            header('Location: ../staff.php?error=email_exists');
        } else if (strpos($errorMessage, "Invalid phone") !== false) {
            header('Location: ../staff.php?error=invalid_phone');
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