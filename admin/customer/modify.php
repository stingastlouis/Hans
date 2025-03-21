<?php
include '../../configs/db.php';
include '../../configs/timezoneConfigs.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $customerId = $_POST['customer_id'];
    $fullname = $_POST['customer_fullname'];
    $email = $_POST['customer_email'];
    $phone = $_POST['customer_phone'];
    $roleId = $_POST['customer_role_id'];

    try {
        // Start transaction
        $conn->beginTransaction();

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }

        // Check if email already exists for other customer members
        $checkEmail = $conn->prepare("SELECT COUNT(*) FROM Customer WHERE Email = ? AND Id != ?");
        $checkEmail->execute([$email, $customerId]);
        if ($checkEmail->fetchColumn() > 0) {
            throw new Exception("Email already exists");
        }

        // Validate phone number (basic validation - adjust as needed)
        if (!preg_match("/^[0-9+\-\s()]*$/", $phone)) {
            throw new Exception("Invalid phone number format");
        }

        // Check if customer exists
        $checkCustomer = $conn->prepare("SELECT COUNT(*) FROM Customer WHERE Id = ?");
        $checkCustomer->execute([$customerId]);
        if ($checkCustomer->fetchColumn() == 0) {
            throw new Exception("Customer member not found");
        }

        // Update customer member in the database
        $stmt = $conn->prepare("UPDATE Customer 
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
            $customerId
        ]);

        // Check if the update was successful
        if ($stmt->rowCount() >= 0) { // Using >= 0 because UPDATE might not change any values
            // Commit transaction
            $conn->commit();

            // Redirect to the customer list page with success message
            header('Location: ../customer.php?success=2'); // Using 2 to differentiate from add success
            exit;
        } else {
            throw new Exception("Error: Unable to update the customer member in the database.");
        }

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollBack();
        
        // Handle specific error messages
        $errorMessage = $e->getMessage();
        if (strpos($errorMessage, "Invalid email") !== false) {
            header('Location: ../customer.php?error=invalid_email');
        } else if (strpos($errorMessage, "Email already exists") !== false) {
            header('Location: ../customer.php?error=email_exists');
        } else if (strpos($errorMessage, "Invalid phone") !== false) {
            header('Location: ../customer.php?error=invalid_phone');
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