<?php
include '../../configs/db.php';
include '../../configs/timezoneConfigs.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $fullname = $_POST['staff_fullname'];
    $email = $_POST['staff_email'];
    $phone = $_POST['staff_phone'];
    $roleId = $_POST['staff_role_id'];
    $password = $_POST['staff_password'];

    try {
        // Start transaction
        $conn->beginTransaction();

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }

        // Check if email already exists
        $checkEmail = $conn->prepare("SELECT COUNT(*) FROM Staff WHERE Email = ?");
        $checkEmail->execute([$email]);
        if ($checkEmail->fetchColumn() > 0) {
            throw new Exception("Email already exists");
        }

        // Validate phone number (basic validation - adjust as needed)
        if (!preg_match("/^[0-9+\-\s()]*$/", $phone)) {
            throw new Exception("Invalid phone number format");
        }

        // Hash the password
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // Insert staff member into the database
        $stmt = $conn->prepare("INSERT INTO Staff (Fullname, Email, Phone, RoleId, PasswordHash, DateCreated) 
                               VALUES (?, ?, ?, ?, ?, NOW())");
        
        $stmt->execute([
            $fullname,
            $email,
            $phone,
            $roleId,
            $passwordHash
        ]);

        // Check if the insertion was successful
        if ($stmt->rowCount() > 0) {
            // Get the ID of the newly created staff member
            $staffId = $conn->lastInsertId();

            // Commit transaction
            $conn->commit();

            // Redirect to the staff list page with success message
            header('Location: ../staff.php?success=1');
            exit;
        } else {
            throw new Exception("Error: Unable to insert the staff member into the database.");
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